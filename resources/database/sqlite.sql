-- #!sqlite

-- #{ tables
-- #    { players
CREATE TABLE IF NOT EXISTS players (
    playerName VARCHAR(32) PRIMARY KEY NOT NULL,
    petType VARCHAR(100) DEFAULT NULL,
    petName VARCHAR(100) DEFAULT NULL
);
-- #    }
-- #}

-- #{ request
-- #    { insert
-- #      :playerName string
-- #      :petType ?string
-- #      :petName ?string
INSERT OR REPLACE INTO players (playerName, petType, petName) VALUES (:playerName, :petType, :petName);
-- #    }
-- #    { get
-- #      :playerName string
SELECT * FROM players WHERE playerName = :playerName;
-- #    }
-- #}

-- #