-- Suppression des utilisateurs
drop user 'visiteur_medical'@'%';
drop user 'comptable'@'%';
drop user 'administrateur'@'%';
drop user 'dsi'@'%';

-- Création des utilisateurs
CREATE USER 'visiteur_medical'@'%' IDENTIFIED BY 'pwd_vm';
CREATE USER 'comptable'@'%' IDENTIFIED BY 'pwd_compt';
CREATE USER 'administrateur'@'%' IDENTIFIED BY 'pwd_admin';
CREATE USER 'dsi'@'%' IDENTIFIED BY 'pwd_admin';

-- Visiteur médical : rwx rw- r--
GRANT SELECT, INSERT, UPDATE ON gsbV2.* TO 'visiteur_medical';

-- Comptable : r-- rwx r--
GRANT SELECT ON gsbV2.* TO 'comptable';
GRANT INSERT, UPDATE ON gsbV2.* TO 'comptable';

-- Administrateur : rwx rwx rw-
GRANT ALL PRIVILEGES ON gsbV2.* TO 'administrateur' WITH GRANT OPTION;
REVOKE DELETE ON gsbV2.* FROM 'administrateur';

-- Administrateur système (DSI) : rwx rwx rwx
GRANT ALL PRIVILEGES ON gsbV2.* TO 'dsi' WITH GRANT OPTION;

FLUSH PRIVILEGES;
