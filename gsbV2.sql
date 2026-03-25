CREATE DATABASE IF NOT EXISTS gsbV2;
USE gsbV2;

CREATE TABLE Etat (
    id CHAR(2) PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE visiteur (
    id CHAR(4) PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    login VARCHAR(50),
    mdp VARCHAR(50),
    adresse VARCHAR(100),
    cp INT,
    ville VARCHAR(50),
    dateEmbauche DATE
) ENGINE=InnoDB;

CREATE TABLE comptable (
    id CHAR(4) PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    login VARCHAR(50),
    mdp VARCHAR(50),
    adresse VARCHAR(100),
    cp INT,
    ville VARCHAR(50),
    dateEmbauche DATE
) ENGINE=InnoDB;

CREATE TABLE administrateur (
    id CHAR(4) PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    login VARCHAR(50),
    mdp VARCHAR(50),
    adresse VARCHAR(100),
    cp INT,
    ville VARCHAR(50),
    dateEmbauche DATE
) ENGINE=InnoDB;

CREATE TABLE FraisForfait (
    id CHAR(3) PRIMARY KEY,
    libelle VARCHAR(50),
    montant DECIMAL(10,2)
) ENGINE=InnoDB;

CREATE TABLE FicheFrais (
    idVisiteur CHAR(4),
    mois CHAR(6),
    nbJustificatif INT,
    montantValide DECIMAL(10,2),
    dateModif DATE,
    idEtat CHAR(2),
    PRIMARY KEY (idVisiteur, mois),
    FOREIGN KEY (idVisiteur) REFERENCES visiteur(id),
    FOREIGN KEY (idEtat) REFERENCES Etat(id)
) ENGINE=InnoDB;

CREATE TABLE LigneFraisForfait (
    idVisiteur CHAR(4),
    mois CHAR(6),
    idFraisForfait CHAR(3),
    quantite INT,
    PRIMARY KEY (idVisiteur, mois, idFraisForfait),
    FOREIGN KEY (idVisiteur, mois) REFERENCES FicheFrais(idVisiteur, mois),
    FOREIGN KEY (idFraisForfait) REFERENCES FraisForfait(id)
) ENGINE=InnoDB;

CREATE TABLE LigneFraisHorsForfait (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idVisiteur CHAR(4),
    mois CHAR(6),
    libelle VARCHAR(100),
    date DATE,
    montant DECIMAL(10,2),
    FOREIGN KEY (idVisiteur, mois) REFERENCES FicheFrais(idVisiteur, mois)
) ENGINE=InnoDB;

INSERT INTO Etat VALUES ('RB','Remboursée');
INSERT INTO Etat VALUES ('CL','Saisie clôturée');
INSERT INTO Etat VALUES ('CR','Fiche créée, saisie en cours');
INSERT INTO Etat VALUES ('VA','Validée et mise en paiement');
INSERT INTO FraisForfait VALUES ('ETP','Forfait Etape',110.0);
INSERT INTO FraisForfait VALUES ('KM','Frais Kilométrique',0.62);
INSERT INTO FraisForfait VALUES ('NUI','Nuitée Hôtel',80.0);
INSERT INTO FraisForfait VALUES ('REP','Repas Restaurant',25.0);
INSERT INTO visiteur VALUES ('a131','Villechalane','Louis','lvillachane','jux7g','8 rue des Charmes',46000,'Cahors','2005-12-21');
INSERT INTO visiteur VALUES ('a17','Andre','David','dandre','oppg5','1 rue Petit',46200,'Lalbenque','1998-11-23');
INSERT INTO visiteur VALUES ('a55','Bedos','Christian','cbedos','gmhxd','1 rue Peranud',46250,'Montcuq','1995-01-12');
INSERT INTO visiteur VALUES ('a93','Tusseau','Louis','ltusseau','ktp3s','22 rue des Ternes',46123,'Gramat','2000-05-01');
INSERT INTO visiteur VALUES ('b13','Bentot','Pascal','pbentot','doyw1','11 allée des Cerises',46512,'Bessines','1992-07-09');
INSERT INTO visiteur VALUES ('b16','Bioret','Luc','lbioret','hrjfs','1 Avenue gambetta',46000,'Cahors','1998-05-11');
INSERT INTO visiteur VALUES ('b19','Bunisset','Francis','fbunisset','4vbnd','10 rue des Perles',93100,'Montreuil','1987-10-21');
INSERT INTO visiteur VALUES ('b25','Bunisset','Denise','dbunisset','s1y1r','23 rue Manin',75019,'paris','2010-12-05');
INSERT INTO visiteur VALUES ('b28','Cacheux','Bernard','bcacheux','uf7r3','114 rue Blanche',75017,'Paris','2009-11-12');
INSERT INTO visiteur VALUES ('b34','Cadic','Eric','ecadic','6u8dc','123 avenue de la République',75011,'Paris','2008-09-23');
INSERT INTO visiteur VALUES ('b4','Charoze','Catherine','ccharoze','u817o','100 rue Petit',75019,'Paris','2005-11-12');
INSERT INTO visiteur VALUES ('b50','Clepkens','Christophe','cclepkens','bw1us','12 allée des Anges',93230,'Romainville','2003-08-11');
INSERT INTO visiteur VALUES ('b59','Cottin','Vincenne','vcottin','2hoh9','36 rue Des Roches',93100,'Monteuil','2001-11-18');
INSERT INTO visiteur VALUES ('c14','Daburon','François','fdaburon','7oqpv','13 rue de Chanzy',94000,'Créteil','2002-02-11');
INSERT INTO visiteur VALUES ('c3','De','Philippe','pde','gk9kx','13 rue Barthes',94000,'Créteil','2010-12-14');
INSERT INTO visiteur VALUES ('c54','Debelle','Michel','mdebelle','od5rt','181 avenue Barbusse',93210,'Rosny','2006-11-23');
INSERT INTO visiteur VALUES ('d13','Debelle','Jeanne','jdebelle','nvwqq','134 allée des Joncs',44000,'Nantes','2000-05-11');
INSERT INTO visiteur VALUES ('d51','Debroise','Michel','mdebroise','sghkb','2 Bld Jourdain',44000,'Nantes','2001-04-17');
INSERT INTO visiteur VALUES ('e22','Desmarquest','Nathalie','ndesmarquest','f1fob','14 Place d Arc',45000,'Orléans','2005-11-12');
INSERT INTO visiteur VALUES ('e24','Desnost','Pierre','pdesnost','4k2o5','16 avenue des Cèdres',23200,'Guéret','2001-02-05');
INSERT INTO visiteur VALUES ('e39','Dudouit','Frédéric','fdudouit','44im8','18 rue de l église',23120,'GrandBourg','2000-08-01');
INSERT INTO visiteur VALUES ('e49','Duncombe','Claude','cduncombe','qf77j','19 rue de la tour',23100,'La souteraine','1987-10-10');
INSERT INTO visiteur VALUES ('e5','Enault-Pascreau','Céline','cenault','y2qdu','25 place de la gare',23200,'Gueret','1995-09-01');
INSERT INTO visiteur VALUES ('e52','Eynde','Valérie','veynde','i7sn3','3 Grand Place',13015,'Marseille','1999-11-01');
INSERT INTO visiteur VALUES ('f21','Finck','Jacques','jfinck','mpb3t','10 avenue du Prado',13002,'Marseille','2001-11-10');
INSERT INTO visiteur VALUES ('f39','Frémont','Fernande','ffremont','xs5tq','4 route de la mer',13012,'Allauh','1998-10-01');
INSERT INTO visiteur VALUES ('f4','Gest','Alain','agest','dywvt','30 avenue de la mer',13025,'Berre','1985-11-01');
INSERT INTO comptable VALUES ('c1','Colombe','Marie','comptable1','comp123','73 rue des Coquelicots',56000,'Lorient','2000-02-21');
INSERT INTO comptable VALUES ('c2','Lemoine','Jean','comptable2','comp456','12 avenue de la République',75011,'Paris','2005-09-15');
INSERT INTO comptable VALUES ('c3','Dupont','Sophie','comptable3','comp789','5 rue de la Paix',75002,'Paris','2010-01-10');
INSERT INTO administrateur VALUES ('a1','Prince','Petit','admin','password','1 rue de l Admin',75000,'Paris','2000-01-01');
INSERT INTO administrateur VALUES ('a2','Buneo','Kinder','admin2','admin456','2 rue de l Admin',75000,'Paris','2005-01-01');
INSERT INTO administrateur VALUES ('a3','Bob','Léponge','admin3','admin789','3 rue de l Admin',75000,'Paris','2010-01-01');
INSERT INTO FicheFrais VALUES ('a131','202404',8,0.00,'2024-04-30','CR');
INSERT INTO FicheFrais VALUES ('a17','202404',5,0.00,'2024-04-30','CR');
INSERT INTO FicheFrais VALUES ('a55','202404',3,0.00,'2024-04-30','CR');
INSERT INTO LigneFraisForfait VALUES ('a131','202404','ETP',2);
INSERT INTO LigneFraisForfait VALUES ('a131','202404','KM',150);
INSERT INTO LigneFraisForfait VALUES ('a131','202404','NUI',3);
INSERT INTO LigneFraisForfait VALUES ('a131','202404','REP',5);
INSERT INTO LigneFraisHorsForfait (idVisiteur, mois, libelle, date, montant) VALUES ('a131','202404','Taxi pour l\'aéroport','2024-04-15',45.00);
INSERT INTO LigneFraisHorsForfait (idVisiteur, mois, libelle, date, montant) VALUES ('a131','202404','Dîner d\'affaires','2024-04-20',60.00);

