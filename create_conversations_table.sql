-- Création de la table conversations (optionnel)
CREATE TABLE IF NOT EXISTS conversations (
    id SERIAL PRIMARY KEY,
    user1_id INTEGER NOT NULL,
    user2_id INTEGER NOT NULL,
    last_message_date TIMESTAMP DEFAULT NOW(),
    message_count INTEGER DEFAULT 0,
    last_message_content TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    -- Contrainte pour éviter les doublons
    UNIQUE(user1_id, user2_id),
    
    -- Clés étrangères
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour optimiser les requêtes
CREATE INDEX IF NOT EXISTS idx_conversations_user1 ON conversations(user1_id);
CREATE INDEX IF NOT EXISTS idx_conversations_user2 ON conversations(user2_id);
CREATE INDEX IF NOT EXISTS idx_conversations_last_message ON conversations(last_message_date);

-- Fonction pour mettre à jour automatiquement les conversations
CREATE OR REPLACE FUNCTION update_conversation()
RETURNS TRIGGER AS $$
BEGIN
    -- Insérer ou mettre à jour la conversation
    INSERT INTO conversations (user1_id, user2_id, last_message_date, message_count, last_message_content)
    VALUES (
        LEAST(NEW.sender, NEW.receveir),
        GREATEST(NEW.sender, NEW.receveir),
        NEW.date_creation,
        1,
        NEW.contenu
    )
    ON CONFLICT (user1_id, user2_id)
    DO UPDATE SET
        last_message_date = NEW.date_creation,
        message_count = conversations.message_count + 1,
        last_message_content = NEW.contenu,
        updated_at = NOW();
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour mettre à jour automatiquement les conversations
DROP TRIGGER IF EXISTS trigger_update_conversation ON message;
CREATE TRIGGER trigger_update_conversation
    AFTER INSERT ON message
    FOR EACH ROW
    EXECUTE FUNCTION update_conversation(); 