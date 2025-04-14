<?php
// Classe pour gérer la base de données JSON
class JsonDatabase {
    private static $instance = null;
    private $dataDir;
    private $tables = [];
    
    private function __construct() {
        // Définir le répertoire de données
        $this->dataDir = dirname(__DIR__) . '/database';
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
        
        // Initialiser les tables
        $this->initializeTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new JsonDatabase();
        }
        return self::$instance;
    }
    
    // Initialiser les tables
    private function initializeTables() {
        $tables = ['users', 'projects', 'quotes', 'messages', 'admin_messages'];
        
        foreach ($tables as $table) {
            $filePath = $this->dataDir . '/' . $table . '.json';
            
            if (!file_exists($filePath)) {
                file_put_contents($filePath, json_encode([]));
            }
            
            $this->tables[$table] = json_decode(file_get_contents($filePath), true) ?: [];
        }
    }
    
    // Sauvegarder une table
    private function saveTable($tableName) {
        $filePath = $this->dataDir . '/' . $tableName . '.json';
        file_put_contents($filePath, json_encode($this->tables[$tableName], JSON_PRETTY_PRINT));
    }
    
    // Obtenir tous les enregistrements d'une table
    public function getAll($tableName) {
        return $this->tables[$tableName] ?? [];
    }
    
    // Trouver un enregistrement par ID
    public function findById($tableName, $id) {
        foreach ($this->tables[$tableName] as $record) {
            if ($record['id'] == $id) {
                return $record;
            }
        }
        return null;
    }
    
    // Trouver des enregistrements par condition
    public function findBy($tableName, $conditions) {
        $results = [];
        
        foreach ($this->tables[$tableName] as $record) {
            $match = true;
            
            foreach ($conditions as $key => $value) {
                if (!isset($record[$key]) || $record[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                $results[] = $record;
            }
        }
        
        return $results;
    }
    
    // Trouver un seul enregistrement par condition
    public function findOneBy($tableName, $conditions) {
        $results = $this->findBy($tableName, $conditions);
        return !empty($results) ? $results[0] : null;
    }
    
    // Insérer un nouvel enregistrement
    public function insert($tableName, $data) {
        // Générer un ID
        $maxId = 0;
        foreach ($this->tables[$tableName] as $record) {
            if ($record['id'] > $maxId) {
                $maxId = $record['id'];
            }
        }
        
        $data['id'] = $maxId + 1;
        
        // Ajouter la date de création si elle n'existe pas
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Ajouter l'enregistrement
        $this->tables[$tableName][] = $data;
        
        // Sauvegarder la table
        $this->saveTable($tableName);
        
        return $data['id'];
    }
    
    // Mettre à jour un enregistrement
    public function update($tableName, $id, $data) {
        foreach ($this->tables[$tableName] as $key => $record) {
            if ($record['id'] == $id) {
                // Mettre à jour les champs
                foreach ($data as $field => $value) {
                    $this->tables[$tableName][$key][$field] = $value;
                }
                
                // Ajouter la date de mise à jour
                $this->tables[$tableName][$key]['updated_at'] = date('Y-m-d H:i:s');
                
                // Sauvegarder la table
                $this->saveTable($tableName);
                
                return true;
            }
        }
        
        return false;
    }
    
    // Supprimer un enregistrement
    public function delete($tableName, $id) {
        foreach ($this->tables[$tableName] as $key => $record) {
            if ($record['id'] == $id) {
                // Supprimer l'enregistrement
                unset($this->tables[$tableName][$key]);
                
                // Réindexer le tableau
                $this->tables[$tableName] = array_values($this->tables[$tableName]);
                
                // Sauvegarder la table
                $this->saveTable($tableName);
                
                return true;
            }
        }
        
        return false;
    }
    
    // Compter les enregistrements
    public function count($tableName, $conditions = []) {
        if (empty($conditions)) {
            return count($this->tables[$tableName]);
        } else {
            return count($this->findBy($tableName, $conditions));
        }
    }
    
    // Méthode pour préparer une requête SQL (simulation PDO)
    public function prepare($sql) {
        return new JsonPreparedStatement($this, $sql);
    }
    
    // Méthode pour exécuter une requête SQL (simulation PDO)
    public function query($sql, $params = []) {
        $stmt = new JsonPreparedStatement($this, $sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    // Méthode pour analyser et exécuter une requête SQL
    public function executeQuery($sql, $params = []) {
        // Analyser la requête SQL pour déterminer l'action à effectuer
        $sql = trim($sql);
        
        // SELECT COUNT(*)
        if (preg_match('/^SELECT\s+COUNT\(\*\)\s+FROM\s+(\w+)(?:\s+WHERE\s+(.*?))?$/i', $sql, $matches)) {
            $table = $matches[1];
            $where = isset($matches[2]) ? $matches[2] : null;
            
            if ($where) {
                $conditions = $this->parseWhereConditions($where, $params);
                $count = 0;
                
                foreach ($this->tables[$table] as $record) {
                    $match = true;
                    
                    foreach ($conditions as $condition) {
                        $field = $condition['field'];
                        $operator = $condition['operator'];
                        $value = $condition['value'];
                        
                        if (!isset($record[$field])) {
                            $match = false;
                            break;
                        }
                        
                        switch ($operator) {
                            case '=':
                                if ($record[$field] != $value) $match = false;
                                break;
                            case '>':
                                if ($record[$field] <= $value) $match = false;
                                break;
                            case '<':
                                if ($record[$field] >= $value) $match = false;
                                break;
                            case '!=':
                                if ($record[$field] == $value) $match = false;
                                break;
                        }
                        
                        if (!$match) break;
                    }
                    
                    if ($match) {
                        $count++;
                    }
                }
                
                return $count;
            } else {
                return count($this->tables[$table]);
            }
        }
        
        // SELECT
        else if (preg_match('/^SELECT (.*?) FROM (\w+)(?:\s+WHERE\s+(.*?))?(?:\s+ORDER BY\s+(.*?))?(?:\s+LIMIT\s+(\d+)(?:\s*,\s*(\d+))?)?$/i', $sql, $matches)) {
            $table = $matches[2];
            $where = isset($matches[3]) ? $matches[3] : null;
            
            $results = $this->tables[$table];
            
            // Filtrer les résultats si WHERE est spécifié
            if ($where) {
                $conditions = $this->parseWhereConditions($where, $params);
                $filteredResults = [];
                
                foreach ($results as $record) {
                    $match = true;
                    
                    foreach ($conditions as $condition) {
                        $field = $condition['field'];
                        $operator = $condition['operator'];
                        $value = $condition['value'];
                        
                        if (!isset($record[$field])) {
                            $match = false;
                            break;
                        }
                        
                        switch ($operator) {
                            case '=':
                                if ($record[$field] != $value) $match = false;
                                break;
                            case '>':
                                if ($record[$field] <= $value) $match = false;
                                break;
                            case '<':
                                if ($record[$field] >= $value) $match = false;
                                break;
                            case '!=':
                                if ($record[$field] == $value) $match = false;
                                break;
                        }
                        
                        if (!$match) break;
                    }
                    
                    if ($match) {
                        $filteredResults[] = $record;
                    }
                }
                
                $results = $filteredResults;
            }
            
            return $results;
        }
        
        // INSERT
        else if (preg_match('/^INSERT INTO (\w+)\s*\((.*?)\)\s*VALUES\s*\((.*?)\)$/i', $sql, $matches)) {
            $table = $matches[1];
            $fields = array_map('trim', explode(',', $matches[2]));
            $values = array_map('trim', explode(',', $matches[3]));
            
            $data = [];
            foreach ($fields as $i => $field) {
                $field = trim($field, '`');
                $value = $values[$i];
                
                if ($value === '?') {
                    $data[$field] = $params[$i];
                } else {
                    $data[$field] = trim($value, "'\"");
                }
            }
            
            return $this->insert($table, $data);
        }
        
        // UPDATE
        else if (preg_match('/^UPDATE (\w+) SET (.*?)(?:\s+WHERE\s+(.*?))?$/i', $sql, $matches)) {
            $table = $matches[1];
            $set = $matches[2];
            $where = isset($matches[3]) ? $matches[3] : null;
            
            $setFields = array_map('trim', explode(',', $set));
            $data = [];
            
            $paramIndex = 0;
            foreach ($setFields as $setField) {
                list($field, $value) = array_map('trim', explode('=', $setField));
                $field = trim($field, '`');
                
                if ($value === '?') {
                    $data[$field] = $params[$paramIndex++];
                } else {
                    $data[$field] = trim($value, "'\"");
                }
            }
            
            if ($where) {
                $conditions = $this->parseWhereConditions($where, array_slice($params, $paramIndex));
                $updated = 0;
                
                foreach ($this->tables[$table] as $key => $record) {
                    $match = true;
                    
                    foreach ($conditions as $condition) {
                        $field = $condition['field'];
                        $operator = $condition['operator'];
                        $value = $condition['value'];
                        
                        if (!isset($record[$field])) {
                            $match = false;
                            break;
                        }
                        
                        switch ($operator) {
                            case '=':
                                if ($record[$field] != $value) $match = false;
                                break;
                            case '>':
                                if ($record[$field] <= $value) $match = false;
                                break;
                            case '<':
                                if ($record[$field] >= $value) $match = false;
                                break;
                            case '!=':
                                if ($record[$field] == $value) $match = false;
                                break;
                        }
                        
                        if (!$match) break;
                    }
                    
                    if ($match) {
                        $this->update($table, $record['id'], $data);
                        $updated++;
                    }
                }
                
                return $updated;
            } else {
                $updated = 0;
                
                foreach ($this->tables[$table] as $record) {
                    $this->update($table, $record['id'], $data);
                    $updated++;
                }
                
                return $updated;
            }
        }
        
        // DELETE
        else if (preg_match('/^DELETE FROM (\w+)(?:\s+WHERE\s+(.*?))?$/i', $sql, $matches)) {
            $table = $matches[1];
            $where = isset($matches[2]) ? $matches[2] : null;
            
            if ($where) {
                $conditions = $this->parseWhereConditions($where, $params);
                $deleted = 0;
                
                // Parcourir les enregistrements en sens inverse pour éviter les problèmes d'index
                for ($i = count($this->tables[$table]) - 1; $i >= 0; $i--) {
                    $record = $this->tables[$table][$i];
                    $match = true;
                    
                    foreach ($conditions as $condition) {
                        $field = $condition['field'];
                        $operator = $condition['operator'];
                        $value = $condition['value'];
                        
                        if (!isset($record[$field])) {
                            $match = false;
                            break;
                        }
                        
                        switch ($operator) {
                            case '=':
                                if ($record[$field] != $value) $match = false;
                                break;
                            case '>':
                                if ($record[$field] <= $value) $match = false;
                                break;
                            case '<':
                                if ($record[$field] >= $value) $match = false;
                                break;
                            case '!=':
                                if ($record[$field] == $value) $match = false;
                                break;
                        }
                        
                        if (!$match) break;
                    }
                    
                    if ($match) {
                        unset($this->tables[$table][$i]);
                        $deleted++;
                    }
                }
                
                // Réindexer le tableau
                $this->tables[$table] = array_values($this->tables[$table]);
                
                // Sauvegarder la table
                $this->saveTable($table);
                
                return $deleted;
            } else {
                $deleted = count($this->tables[$table]);
                $this->tables[$table] = [];
                
                // Sauvegarder la table
                $this->saveTable($table);
                
                return $deleted;
            }
        }
        
        return false;
    }
    
    private function parseWhereConditions($where, $params) {
        $conditions = [];
        $parts = preg_split('/\s+(?:AND|OR)\s+/i', $where);
        
        $paramIndex = 0;
        foreach ($parts as $part) {
            if (preg_match('/([a-zA-Z0-9_]+)\s*([=<>!]+)\s*\?/i', $part, $matches)) {
                $field = trim($matches[1], '`');
                $operator = $matches[2];
                $value = $params[$paramIndex++];
                
                $conditions[] = [
                    'field' => $field,
                    'operator' => $operator,
                    'value' => $value
                ];
            }
        }
        
        return $conditions;
    }
}

// Classe pour simuler un PDOStatement
class JsonPreparedStatement {
    private $db;
    private $sql;
    private $params = [];
    private $result = null;
    private $position = 0;
    
    public function __construct($db, $sql) {
        $this->db = $db;
        $this->sql = $sql;
    }
    
    public function bindParam($param, &$variable, $type = null) {
        $this->params[$param] = $variable;
        return true;
    }
    
    public function bindValue($param, $value, $type = null) {
        $this->params[$param] = $value;
        return true;
    }
    
    public function execute($params = null) {
        if ($params !== null) {
            $this->params = array_values($params);
        }
        
        $this->result = $this->db->executeQuery($this->sql, $this->params);
        $this->position = 0;
        
        return true;
    }
    
    public function fetch($fetchStyle = null) {
        if (is_array($this->result)) {
            if ($this->position >= count($this->result)) {
                return false;
            }
            
            return $this->result[$this->position++];
        }
        
        return false;
    }
    
    public function fetchAll($fetchStyle = null) {
        if (is_array($this->result)) {
            return $this->result;
        }
        
        return [];
    }
    
    public function fetchColumn($column = 0) {
        if (is_numeric($this->result)) {
            return $this->result;
        }
        
        if (is_array($this->result) && $this->position < count($this->result)) {
            $row = $this->result[$this->position++];
            if (is_numeric($column)) {
                $keys = array_keys($row);
                if (isset($keys[$column]) && isset($row[$keys[$column]])) {
                    return $row[$keys[$column]];
                }
            } else if (isset($row[$column])) {
                return $row[$column];
            }
        }
        
        return 0;
    }
    
    public function rowCount() {
        if (is_array($this->result)) {
            return count($this->result);
        } else if (is_numeric($this->result)) {
            return $this->result;
        }
        
        return 0;
    }
}
?>
