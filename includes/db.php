<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/json_db.php';

/**
 * Classe Database
 * Gère la connexion à la base de données et les opérations CRUD
 */
class Database {
    private static $instance = null;
    private $jsonDb;
    
    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        $this->jsonDb = JsonDatabase::getInstance();
    }
    
    /**
     * Obtenir l'instance unique de la classe (Singleton)
     * @return Database Instance unique de la classe
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtenir la connexion à la base de données
     * @return JsonDatabase Objet de connexion à la base de données
     */
    public function getConnection() {
        return $this->jsonDb;
    }
    
    /**
     * Exécute une requête SQL
     * @param string $sql Requête SQL à exécuter
     * @param array $params Paramètres de la requête
     * @return JsonQueryResult Résultat de la requête
     */
    public function query($sql, $params = []) {
        $result = $this->jsonDb->query($sql, $params);
        // Ensure the result is wrapped in JsonQueryResult
        if (!($result instanceof JsonQueryResult)) {
            return new JsonQueryResult($result);
        }
        return $result;
    }
    
    /**
     * Parse les conditions WHERE pour les requêtes SQL
     * @param array|string $where Conditions WHERE
     * @param array $params Paramètres à lier
     * @return array Tableau contenant la clause WHERE et les paramètres
     */
    public function parseWhereConditions($where, $params = []) {
        if (method_exists($this->jsonDb, 'parseWhereConditions')) {
            return $this->jsonDb->parseWhereConditions($where, $params);
        } else {
            // Fallback implementation if method doesn't exist in JsonDatabase
            $whereClause = '';
            $whereParams = [];
            
            if (is_string($where) && !empty($where)) {
                $whereClause = $where;
                $whereParams = $params;
            } elseif (is_array($where) && !empty($where)) {
                $conditions = [];
                foreach ($where as $field => $value) {
                    $conditions[] = "$field = ?";
                    $whereParams[] = $value;
                }
                $whereClause = implode(' AND ', $conditions);
            }
            
            return ['clause' => $whereClause, 'params' => $whereParams];
        }
    }
    
    /**
     * Prépare une requête SQL
     * @param string $sql Requête SQL à préparer
     * @return JsonPreparedStatement Requête préparée
     */
    public function prepare($sql) {
        return $this->jsonDb->prepare($sql);
    }
    
    /**
     * Insère des données dans une table
     * @param string $table Nom de la table
     * @param array $data Données à insérer
     * @return int|bool ID de l'enregistrement inséré ou false en cas d'échec
     */
    public function insert($table, $data) {
        if (method_exists($this->jsonDb, 'insert')) {
            return $this->jsonDb->insert($table, $data);
        }
        
        // Fallback implementation
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->prepare($sql);
        
        if ($stmt->execute(array_values($data))) {
            return $this->jsonDb->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Met à jour des données dans une table
     * @param string $table Nom de la table
     * @param array $data Données à mettre à jour
     * @param array|string $where Condition WHERE
     * @param array $whereParams Paramètres pour la condition WHERE
     * @return int Nombre de lignes affectées
     */
    public function update($table, $data, $where, $whereParams = []) {
        if (method_exists($this->jsonDb, 'update')) {
            return $this->jsonDb->update($table, $data, $where, $whereParams);
        }
        
        // Fallback implementation
        $setClauses = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setClauses[] = "$column = ?";
            $params[] = $value;
        }
        
        $whereResult = $this->parseWhereConditions($where, $whereParams);
        $whereClause = $whereResult['clause'];
        $params = array_merge($params, $whereResult['params']);
        
        $sql = "UPDATE $table SET " . implode(', ', $setClauses);
        if (!empty($whereClause)) {
            $sql .= " WHERE $whereClause";
        }
        
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Supprime des données d'une table
     * @param string $table Nom de la table
     * @param array|string $where Condition WHERE
     * @param array $params Paramètres pour la condition WHERE
     * @return int Nombre de lignes affectées
     */
    public function delete($table, $where, $params = []) {
        if (method_exists($this->jsonDb, 'delete')) {
            return $this->jsonDb->delete($table, $where, $params);
        }
        
        // Fallback implementation
        $whereResult = $this->parseWhereConditions($where, $params);
        $whereClause = $whereResult['clause'];
        $params = $whereResult['params'];
        
        $sql = "DELETE FROM $table";
        if (!empty($whereClause)) {
            $sql .= " WHERE $whereClause";
        }
        
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Recherche des enregistrements dans une table
     * @param string $table Nom de la table
     * @param array|string $where Condition WHERE
     * @param array $params Paramètres pour la condition WHERE
     * @param string $orderBy Clause ORDER BY
     * @param int $limit Limite de résultats
     * @param int $offset Décalage des résultats
     * @return JsonQueryResult Résultat de la recherche
     */
    public function find($table, $where = null, $params = [], $orderBy = null, $limit = null, $offset = null) {
        if (method_exists($this->jsonDb, 'find')) {
            return $this->jsonDb->find($table, $where, $params, $orderBy, $limit, $offset);
        }
        
        // Fallback implementation
        $sql = "SELECT * FROM $table";
        
        if ($where) {
            $whereResult = $this->parseWhereConditions($where, $params);
            $whereClause = $whereResult['clause'];
            $params = $whereResult['params'];
            
            if (!empty($whereClause)) {
                $sql .= " WHERE $whereClause";
            }
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
            if ($offset) {
                $sql .= " OFFSET $offset";
            }
        }
        
        return $this->query($sql, $params);
    }
}

// Classe pour simuler un PDOStatement
class JsonQueryResult {
    private $records;
    private $position = 0;
    
    public function __construct($records) {
        // Ensure $records is always an array
        $this->records = is_array($records) ? $records : [];
    }
    
    public function fetch($fetchStyle = null) {
        if ($this->position >= count($this->records)) {
            return false;
        }
        
        $record = $this->records[$this->position];
        $this->position++;
        return $record;
    }
    
    public function fetchAll($fetchStyle = null) {
        return $this->records;
    }
    
    public function rowCount() {
        return count($this->records);
    }
    
    // Add method to reset the position
    public function reset() {
        $this->position = 0;
        return $this;
    }
}
?>