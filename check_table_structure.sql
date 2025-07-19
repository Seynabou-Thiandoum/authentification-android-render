-- Vérifier la structure de la table message
\d message;

-- Ou avec une requête SQL standard
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name = 'message' 
ORDER BY ordinal_position;

-- Vérifier les données existantes
SELECT * FROM message LIMIT 5; 