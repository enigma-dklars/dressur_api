-- MariaDB dump 10.19  Distrib 10.11.7-MariaDB, for Linux (x86_64)
--
-- Host: 193.203.168.45    Database: u920435648_dress_dbname
-- ------------------------------------------------------
-- Server version	10.11.7-MariaDB-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `boost`
--

DROP TABLE IF EXISTS `boost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formule_boost_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_exp` datetime NOT NULL,
  `mode` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_566427687BBCC0BB` (`formule_boost_id`),
  KEY `IDX_56642768A76ED395` (`user_id`),
  CONSTRAINT `FK_566427687BBCC0BB` FOREIGN KEY (`formule_boost_id`) REFERENCES `formule_boost` (`id`),
  CONSTRAINT `FK_56642768A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boost`
--

LOCK TABLES `boost` WRITE;
/*!40000 ALTER TABLE `boost` DISABLE KEYS */;
INSERT INTO `boost` VALUES
(1,1,2,'2024-05-18 11:46:21','2024-05-20 11:46:21','Payant'),
(2,7,6,'2024-05-18 12:26:51','2024-09-15 12:26:51','Gratuit'),
(3,1,7,'2024-05-18 18:14:21','2024-05-20 18:14:21','Payant');
/*!40000 ALTER TABLE `boost` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campagne_mail`
--

DROP TABLE IF EXISTS `campagne_mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campagne_mail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `formule_campagne_mail_id` int(11) NOT NULL,
  `titre` longtext NOT NULL,
  `sujet` longtext NOT NULL,
  `replyto` longtext NOT NULL,
  `sendto` longtext NOT NULL,
  `contentmail` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BA143088A76ED395` (`user_id`),
  KEY `IDX_BA143088F4BEC7DE` (`formule_campagne_mail_id`),
  CONSTRAINT `FK_BA143088A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_BA143088F4BEC7DE` FOREIGN KEY (`formule_campagne_mail_id`) REFERENCES `formule_campagne_mail` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campagne_mail`
--

LOCK TABLES `campagne_mail` WRITE;
/*!40000 ALTER TABLE `campagne_mail` DISABLE KEYS */;
INSERT INTO `campagne_mail` VALUES
(1,2,1,'Communication sur la conférence de 🤣','Conférence du 18/12/24','bluelife.tech@gmail.com','dev@gmail.com,dev1@gmail.com,dev2@gmail.com,dev3@gmail.com,dev4@gmail.com,dev5@gmail.com,dev6@gmail.com,dev7@gmail.com,dev8@gmail.com,dev9@gmail.com,dev10@gmail.com','🚀 Découvrez BLUE LIFE TECH - Votre Porte d\'Accès à l\'Informatique et Plus Encore! 🚀\nVous cherchez des solutions informatiques innovantes? Vous êtes passionné par la technologie et le numérique? Ou peut-être êtes-vous à la recherche d\'une opportunité pour développer vos compétences dans le domaine de l\'informatique? Ne cherchez plus, BLUE LIFE TECH est là pour répondre à tous vos besoins!\n💼 Nos Services 💼\nConception de Sites Web 🌐\nDéveloppement d\'Applications Mobiles 📱\nMaintenance Informatique 💻\nGénie Logiciel 🧠\nRéseaux Informatiques & Sécurité 🔒\nGraphisme & Communication 🎨\nÉlectricité & Énergie ⚡\n👨‍🎓 Formations Professionnelles 👩‍🎓\nNous offrons également une gamme complète de formations professionnelles dans tous nos domaines d\'activité. Que vous soyez débutant ou professionnel aguerri, nos programmes de formation vous aideront à acquérir des compétences précieuses pour exceller dans le monde de la technologie.\n👥 Stages & Opportunités 👥\nBLUE LIFE TECH propose des opportunités passionnantes de stage dans tous nos domaines d\'activité. Rejoignez notre équipe et travaillez sur des projets innovants qui stimuleront votre créativité et renforceront vos compétences.\n🌐 Nous Contacter 🌐\nEmail : bluelife.tech@gmail.com 📧\nWhatsApp : +229 58 51 95 56 📱\nSuivez nous sur Instagram, TikTok, YouTube et Facebook pour les dernières mises à jour !\n📢 Partagez cette publication! 📢\nVous pourriez aider quelqu\'un à trouver la carrière de ses rêves ou la solution informatique parfaite. Partagez ce flyer et laissez nous vous accompagner vers un avenir numérique brillant!\n💼 Pourquoi choisir BLUE LIFE TECH ? 💼\nBLUE LIFE TECH est votre partenaire de confiance pour tout ce qui concerne l\'informatique. Nous combinons l\'expertise, l\'innovation et la passion pour vous offrir des solutions sur mesure qui répondent à vos besoins.\n🚀 Rejoignez la révolution numérique avec BLUE LIFE TECH. 🚀\nL\'avenir commence ici! 💻🌟','2024-05-18 11:59:08',3),
(2,6,1,'Chawama ','Nourriture ','alladayesandym@gmail.com','vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com,vchrigene@gmail.com','Poulet ','2024-05-18 12:37:03',2),
(3,7,1,'Elitics Core','Promotion ELITICS CORE ','noegouton@gmail.com','noegouton19@gmail.com,noegouton@gmail.com,samsonno.gou10@gmail.com,goutonnoe@gmail.com,honfofructueux@gmail.com,kotymichelle@gmail.com,eliticscore@gmail.com,elitics.core@tech-center.com,kotymadona@gmail.com,tanvoetoun@gmail.com','Bonjour. Comment portez-vous ? Veillez nous contacter pour vos projets digitaux. Merci','2024-05-18 18:20:30',2);
/*!40000 ALTER TABLE `campagne_mail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact`
--

DROP TABLE IF EXISTS `contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `who_iadd` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`who_iadd`)),
  `who_add_me` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`who_add_me`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4C62E638A76ED395` (`user_id`),
  CONSTRAINT `FK_4C62E638A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact`
--

LOCK TABLES `contact` WRITE;
/*!40000 ALTER TABLE `contact` DISABLE KEYS */;
INSERT INTO `contact` VALUES
(1,1,'[]','[]'),
(2,2,'[]','[6,10]'),
(3,3,'[]','[]'),
(4,4,'[]','[]'),
(5,5,'[]','[]'),
(6,6,'[2]','[]'),
(7,7,'[]','[10]'),
(8,8,'[]','[]'),
(9,9,'[]','[]'),
(10,10,'[7,2]','[]'),
(11,11,'[]','[]'),
(12,12,'[]','[]');
/*!40000 ALTER TABLE `contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts_user`
--

DROP TABLE IF EXISTS `contacts_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_tel` varchar(255) DEFAULT NULL,
  `display_name_tel` varchar(255) DEFAULT NULL,
  `number_tel` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1426 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts_user`
--

LOCK TABLES `contacts_user` WRITE;
/*!40000 ALTER TABLE `contacts_user` DISABLE KEYS */;
INSERT INTO `contacts_user` VALUES
(1,'Alida ','Alida','97947480'),
(2,'Amour ','Amour','91407199'),
(3,'Arnaud ','Arnaud','91127800'),
(4,'Arnaud ','Arnaud','91127800'),
(5,'Arnaud 2','Arnaud 2','61931636'),
(6,'Barthélemy ','Barthélemy','+22959684883'),
(7,'CA COTONOU','CA COTONOU','+22997675302'),
(8,'Chimène 😘','Chimène 😘','+22958479308'),
(9,'Christophe ','Christophe','45080923'),
(10,'Christophe ','Christophe','+22990982438'),
(11,'Coiffeuse ','Coiffeuse','67426539'),
(12,'Corneille AHINON','Corneille AHINON','+22999332885'),
(13,'Cryschou🌹 ','Cryschou🌹','+22966983916'),
(14,'Dada Honon','Dada Honon','+22990344949'),
(15,'Dada Marlene ','Dada Marlene','40126160'),
(16,'Dada Pelagie','Dada Pelagie','97478176'),
(17,'Dressur ✅','Dressur Assistance ✅','+22964044294'),
(18,'Fatima ','Fatima','+24105282651'),
(19,'Fo Janvier','Fo Janvier','97041969'),
(20,'Fo Justin','Fo Justin','+22967916587'),
(21,'Fofo Fructueux','Fofo Fructueux','+22999150275'),
(22,'Fofo José','Fofo José','+22996725924'),
(23,'Fofo.Oxence ','Fofo.Oxence','69464492'),
(24,'Gladys ','Gladys','55465572'),
(25,'GRH IAMD','GRH IAMD','97485289'),
(26,'Honey ','Honey','97542851'),
(27,'Hugues K','Hugues K','+22998121420'),
(28,'Iya Tola','Iya Tola','96325311'),
(29,'Khevine ','Khevine','+22952120054'),
(30,'L ','L','+22950854561'),
(31,'Loko Christian','Loko Christian','+22996545670'),
(32,'M Ana','M Ana','96988742'),
(33,'M Prince','M Prince','65195668'),
(34,'M Dieu_donne','M.Dieu_donne','+22998199431'),
(35,'M Exaucé','M.Exaucé','+22966451861'),
(36,'Madame Francoise','Madame Francoise','+22991656917'),
(37,'Madame Nao','Madame Nao','+22962208082'),
(38,'Maman ','Maman','+22967525825'),
(39,'Maman Aquilas','Maman Aquilas','96560280'),
(40,'Maman Chadrac','Maman Chadrac','97088110'),
(41,'Maman 😘','Maman Chance 😘','96580219'),
(42,'Maman Essé','Maman Essé','+22997881068'),
(43,'Maman Gilles','Maman Gilles','61223854'),
(44,'Maman Rudelle','Maman Rudelle','62190720'),
(45,'Maman Sandy','Maman Sandy','97154451'),
(46,'Mandinatou ','Mandinatou','+22999544850'),
(47,'Mère Gilchrist','Mère Gilchrist','97378801'),
(48,'Michaël.KOTY ','Michaël.KOTY','90974747'),
(49,'Mm ALLAGBE','Mm ALLAGBE','97231695'),
(50,'Mme Anastasie','Mme Anastasie','+22997381994'),
(51,'Mme Annick','Mme Annick','+22996091621'),
(52,'Mme Feldia','Mme Feldia','97492506'),
(53,'Mme Francine','Mme Francine','97256707'),
(54,'Mme Jeannette','Mme Jeannette','+22959326781'),
(55,'Mme Melaine','Mme Melaine','+22967359268'),
(56,'Mme OSSOUBI','Mme OSSOUBI','96023625'),
(57,'Mme Joelle','Mme Rosnie Joelle','+22966817360'),
(58,'Mme.AGUEH ','Mme.AGUEH','96488005'),
(59,'Mme.Claire ','Mme.Claire','69970913'),
(60,'Mme.KOUAGOU ','Mme.KOUAGOU','96301575'),
(61,'Mme.Viteme ','Mme.Viteme','95248528'),
(62,'Mr Corneille','Mr Corneille','66388186'),
(63,'Mr Marc','Mr Marc','+22966684586'),
(64,'Mr Merlieck','Mr Merlieck','+22997030943'),
(65,'Mr Oved','Mr Oved','97045262'),
(66,'Mr Vital','Mr Vital','+22997849486'),
(67,'Mr Yourim','Mr Yourim','96549112'),
(68,'Mum Cherif','Mum Cherif','96595314'),
(69,'Mum Mael','Mum Mael','98986604'),
(70,'Mum 😘','Mum Zoé 😘','66922425'),
(71,'Oncle Marcel','Oncle Marcel','+22969771095'),
(72,'P Gilles','P. Gilles','+22966651537'),
(73,'Pao ','Pao','+22967773112'),
(74,'Paola.KOTY  ','Paola.KOTY','40609718'),
(75,'Papa Avocat','Papa Avocat','97523007'),
(76,'Papa Foumi','Papa Foumi','95408290'),
(77,'Papa KOULIHO','Papa KOULIHO','+22990760194'),
(78,'Papa Rudelle','Papa Rudelle','97121495'),
(79,'Plastic ','Plastic','96348194'),
(80,'Quivy GOUSSANOU','Quivy GOUSSANOU','+22995215480'),
(81,'RAF IAMD','RAF IAMD','96628692'),
(82,'Raïmi ','Raïmi','96461002'),
(83,'Ruth M','Ruth M.','+22990204631'),
(84,'S.Irene ','S.Irene','61808856'),
(85,'Shadraque ','Shadraque','+22958373709'),
(86,'Simon ','Simon','52591844'),
(87,'Tante ','Tante','+22997815115'),
(88,'Tante Merveilles','Tante. Merveilles','+22997783343'),
(89,'Tata Chaussure','Tata Chaussure','67197337'),
(90,'Togbé Kévino','Togbé Kévino','+22990210317'),
(91,'A ','A','94225068'),
(92,'A ','A','94225068'),
(93,'Abdou ASSOUMA','Abdou Malick ASSOUMA','+22995459313'),
(94,'Abiola ','Abiola','96935961'),
(95,'Abiola ','Abiola','96935961'),
(96,'Abou ','Abou','0024105847295'),
(97,'Abou ','Abou','0024105847295'),
(98,'Adebissi ','Adebissi','+22967613276'),
(99,'Adéchinan ','Adéchinan','+22953924197'),
(100,'Adeline ','Adeline','69668657'),
(101,'Adrien ','Adrien','66615102'),
(102,'Adrien ','Adrien','66615102'),
(103,'AGBOGLA Felix','AGBOGLA Felix','65820463'),
(104,'AGBOGLA Felix','AGBOGLA Felix','65820463'),
(105,'Agbokou ','Agbokou','96426607'),
(106,'Agbossou ','Agbossou','60405000'),
(107,'Agbossou ','Agbossou','60405000'),
(108,'AGLI Campus','AGLI Campus','+22996903996'),
(109,'AGLI Campus','AGLI Campus','+22996903996'),
(110,'Ahmed AKALA','Ahmed AKALA','61895460'),
(111,'Ahmed AKALA','Ahmed AKALA','61895460'),
(112,'AHOUNHOSSE ALAIN','AHOUNHOSSE LALANDE ALAIN','+22996083296'),
(113,'Aimé ','Aimé','66635494'),
(114,'Air Faible','Air Faible','+22951227794'),
(115,'Akanni ','Akanni','97796892'),
(116,'Akanni ','Akanni','97796892'),
(117,'Akdg emman','Akdg emman','97781838'),
(118,'AKEL +','AKEL Informatique +','+22963906650'),
(119,'AKEL +','AKEL Informatique +','+22963906650'),
(120,'Akim ','Akim','+22890030947'),
(121,'Akim ','Akim','+22890030947'),
(122,'Akim Notaire','Akim Notaire','62745200'),
(123,'Akim Notaire','Akim Notaire','62745200'),
(124,'Akinotcho ','Akinotcho','97085273'),
(125,'Akinotcho ','Akinotcho','97085273'),
(126,'Alasane 2','Alasane 2','0024105095042'),
(127,'Alasane 2','Alasane 2','0024105095042'),
(128,'Alim Shop','Alim Shop','+22870348098'),
(129,'Alim Shop','Alim Shop','+22870348098'),
(130,'Alonga Le Sorcier  ','Alonga Le Sorcier','0704249499'),
(131,'Alphonse ','Alphonse','+22890438610'),
(132,'Alvin  ','Alvin','0757685205'),
(133,'Alysson ','Alysson','+22967373825'),
(134,'Amadou ','Amadou','0749071452'),
(135,'Amara Douaoudd Le Dix ','Amara Douaoudd Le Dix','0787725983'),
(136,'Amen Fire','Amen Fire','+22966647048'),
(137,'Ami Juste ','Ami Juste','96019074'),
(138,'Amidou ','Amidou','0024107603131'),
(139,'Amidou ','Amidou','0024107603131'),
(140,'Amidou ','Amidou','0024107603131'),
(141,'Aminna ','Aminna','64310041'),
(142,'Amiry😆 ','Amiry😆','+22968738060'),
(143,'Amour Coach','Amour Coach','97735155'),
(144,'Amour Coach','Amour Coach','97735155'),
(145,'Ananouille 😆','Ananouille 😆','+25761929609'),
(146,'Ana×s🫶🏽 ','Ana×s🫶🏽','+22999002424'),
(147,'Ana×s🫶🏽 ','Ana×s🫶🏽','99002424'),
(148,'Anderson FACHINA','Anderson','+22990803778'),
(149,'Anderson FACHINA','Anderson','+22960800573'),
(150,'Anderson ','Anderson','+22990803778'),
(151,'Anderson FACHINA','Anderson FACHINA','+22960800573'),
(152,'Anderson FACHINA ','Anderson FACHINA','+22940547719'),
(153,'Anderson FACHINA ','Anderson FACHINA','+22896959252'),
(154,'Anderson FACHINA ','Anderson FACHINA','+22990803778'),
(155,'Anderson FACHINA ','Anderson FACHINA','+22960800573'),
(156,'Anderson FACHINA ','Anderson FACHINA','+22960800573'),
(157,'Anderson FCN ','Anderson FCN','+22990803778'),
(158,'Andil AB','Andil AB','+22997208098'),
(159,'Andil AB','Andil AB','+22997208098'),
(160,'André 1','André 1','+24106567070'),
(161,'André 1','André 1','+24106567070'),
(162,'André 2','André 2','0024106394991'),
(163,'André 2','André 2','0024106394991'),
(164,'Andrea Adou','Andrea Adou','0703336803'),
(165,'ANDY ','ANDY','90803778'),
(166,'ANDY ','ANDY','90803778'),
(167,'Andy King ','Andy King','0574188076'),
(168,'Andy Steve','Andy Steve','66986666'),
(169,'Ange ','Ange','+2250799507502'),
(170,'Ange Dokui ','Ange Dokui','0749250965'),
(171,'Ange Rie 7 ','Ange Rie 7','0787278120'),
(172,'Angeho ','Angeho','0576935709'),
(173,'Angèle 💓🥳','Angèle 💓🥳','+22952130846'),
(174,'Angelique 2','Angelique 2','95202432'),
(175,'Angelique 2','Angelique 2','95202432'),
(176,'Angelo😹 ','Angelo😹','+22962688943'),
(177,'Anifa ','Anifa','+22967887750'),
(178,'Anifa ','Anifa','+22967887750'),
(179,'Anifa Alimi','Anifa Alimi','+22967887750'),
(180,'Anifa Alimi','Anifa Alimi','+22967887750'),
(181,'Anifa De Mocktahr','Anifa De Mocktahr','+22967887750'),
(182,'Anifa De Mocktahr','Anifa De Mocktahr','+22967887750'),
(183,'Anifath ','Anifath','+22967079271'),
(184,'Anifath ','Anifath','+22967079271'),
(185,'Anifath Sûre)','Anifath (Not Sûre)','+22960405376'),
(186,'AnnaYa😆 ','AnnaYa😆','55028498'),
(187,'Annie ADF','Annie Famyssol ADF','+22995994157'),
(188,'Anriette ','Anriette','0024104237252'),
(189,'Anriette ','Anriette','0024104237252'),
(190,'Anriette ','Anriette','0024104237252'),
(191,'Anti Ga','Anti Ga','05418517'),
(192,'Anti Ga','Anti Ga','05418517'),
(193,'Apull-up 🏀','Apull-up 🏀','+22958880472'),
(194,'Araphat ','Araphat','+22990366370'),
(195,'Ariel ','Ariel','+22994349169'),
(196,'Ariella 2','Ariella 2','+22941262091'),
(197,'Arielle ','Arielle','0768127314'),
(198,'Ariol Cfa🥰','Ariol Cfa🥰','+2250585023176'),
(199,'Aristide ','Aristide','0757651944'),
(200,'Armand ','Armand','+22994697586'),
(201,'Armand ADJBK','Armand ADJBK','41959489'),
(202,'Armand ADJBK','Armand ADJBK','41959489'),
(203,'Armand DJINKPOR','Armand DJINKPOR','+22997278413'),
(204,'Armande ','Armande','61633460'),
(205,'Armandine ANG','Armandine ANG','+22967290769'),
(206,'Armel Saoudien ','Armel Saoudien','+2250545869309'),
(207,'Armelle Deo','Armelle Deo','51877000'),
(208,'Armelle Deo','Armelle Deo','51877000'),
(209,'Arnaud Le Riche ','Arnaud Le Riche','+2250787481506'),
(210,'As Danger Franck ','As Danger Franck','0594812998'),
(211,'Ashab Tech','Ashab Group Tech','+22952295929'),
(212,'Ashab Tech','Ashab Group Tech','+22952295929'),
(213,'Ashley  ','Ashley','0758711511'),
(214,'Ashmed ','Ashmed','+22997916989'),
(215,'Assacino Service  ','Assacino Service','0747518787'),
(216,'Assiaka ADEGNIKA','Assiaka ADEGNIKA','95067465'),
(217,'Assiaka ADEGNIKA','Assiaka ADEGNIKA','95067465'),
(218,'Astrid🦋🦄 ','Astrid🦋🦄','97532519'),
(219,'Astro ','Astro','+22943817495'),
(220,'Astro9 ','Astro9','+22962030808'),
(221,'Atoro 2','Atoro 2','95953880'),
(222,'Attia ','Attia','+2250797261121'),
(223,'Aubin Pcs ','Aubin Pcs','0173528825'),
(224,'Augustin ','Augustin','97095033'),
(225,'Augustin ','Augustin','97095033'),
(226,'Augustin ','Augustin','97095033'),
(227,'Aurel ','Aurel','+22670565691'),
(228,'Autorité Cmc ','Autorité Cmc','0778404959'),
(229,'AZANHOUN Fiacre','AZANHOUN Fiacre','+22996785879'),
(230,'Baba Romsi','Baba Romsi','+22990935187'),
(231,'Badebo ','Badebo','00225059602'),
(232,'Badebo ','Badebo','00225059602'),
(233,'Baki 😎','Baki 😎','+22991672909'),
(234,'Bankolé Augustin','Bankolé Augustin','0022501639434'),
(235,'Bankolé Augustin','Bankolé Augustin','0022501639434'),
(236,'Basile Ordinateur','Basile Ordinateur','+22890943022'),
(237,'Bassarou Yacoubou','Bassarou Yacoubou','+22997602657'),
(238,' ','BAYANI TECH','+22953282828'),
(239,'BAYANI TECH','BAYANI TECH','+22953282828'),
(240,'Béni ','Béni','96815409'),
(241,'Benoît COSIT','Benoît COSIT','+22961061940'),
(242,'Bestyy🔥🤣😭 ','Bestyy🔥🤣😭','+22956529991'),
(243,'Besty🔥🤣😭 ','Besty🔥🤣😭','+22956529991'),
(244,'Bib ','Bib','024105800831'),
(245,'Bib ','Bib','024105800831'),
(246,'Bienvenu ','Bienvenu','64985490'),
(247,'Bienvenue AVA','Bienvenue AVA','0024105386186'),
(248,'Bienvenue Epouse','Bienvenue Epouse','96314965'),
(249,'Bienvenue Epouse','Bienvenue Epouse','96314965'),
(250,'Binks 🔪','Binks 🔪','55281118'),
(251,'Bissiriou De Féfé','Bissiriou De Féfé','+22961612015'),
(252,'Bissiriou De Féfé','Bissiriou De Féfé','+22961612015'),
(253,'Blaise ','Blaise','97688215'),
(254,'  Blaise ','Blaise','91700101'),
(255,'Blaise ','Blaise','97688215'),
(256,'Blandine ','Blandine','61604190'),
(257,'Blandine ','Blandine','61604190'),
(258,'Blandine Maman','Blandine Maman','64303749'),
(259,'Blandine Maman','Blandine Maman','64303749'),
(260,'Blez ','Blez','97688215'),
(261,'Blez ','Blez','97688215'),
(262,'Bobowè ','Bobowè','+22969145881'),
(263,'Bola ','Bola','65698310'),
(264,'Bola ','Bola','65698310'),
(265,'Bor ','Bor','97805294'),
(266,'Bouboule 👑👑','Bouboule','+22991908617'),
(267,'Boxer ','Boxer','+22997375188'),
(268,'Boxer ','Boxer','+22997375188'),
(269,'Brayann Ada','Brayann Ada','0566469463'),
(270,'Brenda Tiony ','Brenda Tiony','0789763091'),
(271,'Brendan🙃 ','Brendan🙃','+22952267075'),
(272,'Brice 😎🥰','Brice DENANKPO 😎🥰','+22953222063'),
(273,'Brice Maurice','Brice Maurice','+352691967338'),
(274,'Briee✨ ','Briee✨','+22962381722'),
(275,'Bruno ','Bruno','97657287'),
(276,'Caleb BIOKOU','Caleb BIOKOU','+22968529101'),
(277,'Caleb BIOKOU','Caleb BIOKOU','+22968529101'),
(278,'Candy🍫🤣 Ysn','Candy🍫🤣','+22940895733'),
(279,'Carelle ','Carelle','+22990881373'),
(280,'Casim Moutairo','Casim Moutairo','+22966995010'),
(281,'Casimir ','Casimir','96486794'),
(282,'Castro ','Castro','0778636468'),
(283,'Celine 2','Celine 2','0024107913368'),
(284,'Celine 2','Celine 2','0024107913368'),
(285,'Celtiis ','Celtiis','40404040'),
(286,'Celtiis ','Celtiis','40404040'),
(287,'Celtiis Modem','Celtiis Modem','40530945'),
(288,'César ','César','66995748'),
(289,'CH 🔪','CH 🔪','+22951518747'),
(290,'Chadrack ','Chadrack','+22996083871'),
(291,'charbel Gnssu','charbel Gnssu','+22960781212'),
(292,'Charbel🥶🐍 ','Charbel🥶🐍','+22990820562'),
(293,'Charle ','Charle','95058088'),
(294,'Charle ','Charle','95058088'),
(295,'Charle 2','Charle 2','97577139'),
(296,'Charle 2','Charle 2','97577139'),
(297,'Charmelo Aho comptable Refuge','Charmelo Aho comptable Refuge','+22997958241'),
(298,'Chauf Tiburce','Chauf Tiburce','+22997680287'),
(299,'Chaussure ','Chaussure','69003601'),
(300,'Chaussure ','Chaussure','69003601'),
(301,'Cheikh😂 ','Cheikh😂','+33644951439'),
(302,'Chemisier MOHAMED','Chemisier MOHAMED','52365112'),
(303,'Chemisier MOHAMED','Chemisier MOHAMED','52365112'),
(304,'Cherif Dovonon','Cherif Dovonon','+22996753635'),
(305,'Cherif Dovonon','Cherif Dovonon','+22996753635'),
(306,'Chifaou ','Chifaou','96500969'),
(307,'chris ','chris','+22953970157'),
(308,'Chris -frh','Chris -frh','+22953318360'),
(309,'Chris -frh','Chris -frh','+22953318360'),
(310,'Christ ','Christ','0554389767'),
(311,'Christa ','Christa','+22999914179'),
(312,'Christian 2','Christian 2','+22994970353'),
(313,'Christian 2','Christian 2','+22994970353'),
(314,'Christian 3','Christian 3','+22966230143'),
(315,'Christian 3','Christian 3','+22966230143'),
(316,'Christiane ','Christiane','63434210'),
(317,'Christiane ','Christiane','63434210'),
(318,'Christiane ','Christiane','0152212143'),
(319,'Christmas ','Christmas','58849282'),
(320,'Ciforq ','Ciforq','67974343'),
(321,'Claire ','Claire','+22968134301'),
(322,'Claude Junior','Claude Junior','66158751'),
(323,'Clavert ','Clavert','97686869'),
(324,'Clavert ','Clavert','97686869'),
(325,'Clémence ','Clémence','+22891223708'),
(326,'Cœur  ','Cœur','0749482419'),
(327,'Colombe 🫶💍','Colombe 🫶💍','+22952440968'),
(328,'Comptable Ept','Comptable Ept','+22961215458'),
(329,'Constant LKT','Constant LKT','+22990642878'),
(330,'Constant LKT','Constant LKT','+22990642878'),
(331,'Contact Joly','Contact Joly','+22994705801'),
(332,'Contact Joly','Contact Joly','+22994705801'),
(333,'Coq ','Coq','67572161'),
(334,'Coq ','Coq','67572161'),
(335,'COSSI Ordinateur','COSSI Ordinateur','+22890239370'),
(336,'Coucou ','Coucou','96729113'),
(337,'Couturier Benn','Couturier Benn','+22891340922'),
(338,'Credo ','Credo','61489095'),
(339,'Credo ','Credo','61489095'),
(340,'Credo ','Credo','61489095'),
(341,'Credo ','Credo','61489095'),
(342,'Crédo AYIVI','Crédo AYIVI','+22962002378'),
(343,'Crédo AYIVI','Crédo AYIVI','+22962002378'),
(344,'Dad ','Dad','+22997695555'),
(345,'Damian ','Damian','+22953932821'),
(346,' ','Daniel VLAVONOU','+22991028193'),
(347,'Daniel VLAVONOU','Daniel VLAVONOU','+22991028193'),
(348,'Daouda ','Daouda','67371908'),
(349,'Daouda ','Daouda','67371908'),
(350,'Darrell ','Darrell','+22991010754'),
(351,'Dasilva ','Dasilva','52405727'),
(352,'Dav Bk','Dav Bk','+22962770282'),
(353,'David ','David','97335128'),
(354,'David ','David','97335128'),
(355,'David La Stars ','David La Stars','0565439779'),
(356,'Debora ','Debora','0757752853'),
(357,'Dedos ','Dedos','+2250778228982'),
(358,'Deen CHABI','Deen CHABI','+22962856656'),
(359,'Deganus🔥 ','Deganus🔥','56035047'),
(360,'Denis Coordonier','Denis Coordonier','62660842'),
(361,'Désiré ','Désiré','+24105602700'),
(362,'Désiré ','Désiré','+24105602700'),
(363,'DG CameoShell','DG CameoShell','61024071'),
(364,'Diane ','Diane','0586423264'),
(365,'Dina CELTIIS ','Dina CELTIIS','97905444'),
(366,'Dine 2','Dine 2','95214633'),
(367,'Dine 2','Dine 2','95214633'),
(368,'Directrice ','Directrice','+22962556886'),
(369,'Directrice Ciforq','Directrice Ciforq','96464629'),
(370,'Donasin ','Donasin','+24105415729'),
(371,'Donasin ','Donasin','+24105415729'),
(372,'Dossou Sèdjro','Dossou Sèdjro','+22995118732'),
(373,'Dressur ✅','Dressur Assistance ✅','+22964044294'),
(374,'Dylan ','Dylan','+22995597525'),
(375,'Dylanne Bro ','Dylanne Bro','0759141420'),
(376,'EAC Pcie','EAC Pcie','+22962925021'),
(377,'Eig 💻','Eig 💻','+22952887777'),
(378,'Elie AHMLT','Elie AHMLT','+18195762129'),
(379,'Elysée Ciforq','Elysée Ciforq','+22795739671'),
(380,'Emman dk','Emman dk','+22960342828'),
(381,'Emmanuel 2','Emmanuel 2','0024106213450'),
(382,'Emmanuel 2','Emmanuel 2','0024106213450'),
(383,'Emmanuel AD','Emmanuel AD','+22999780264'),
(384,'Emmanuel AD','Emmanuel AD','+22999780264'),
(385,'Emmanuel Allamou','Emmanuel Allamou','+22967790000'),
(386,'Emmanuel Allamou','Emmanuel Allamou','+22965099797'),
(387,'Emmanuel Allamou','Emmanuel Allamou','+22965098787'),
(388,'Emmanuel Ciforq','Emmanuel Ciforq','+22954058272'),
(389,'Epouse Désiré','Epouse Désiré','61224048'),
(390,'Eric ','Eric','98845097'),
(391,'Eric ','Eric','98845097'),
(392,'Esdras AGO','Esdras AGO','66099197'),
(393,'ESGT ','ESGT','65325470'),
(394,'Esther gaga','Esther gaga','51823611'),
(395,'Esther J','Esther J','+22991365339'),
(396,'Eugene France','Eugene France','+33641499940'),
(397,'Euri🤧 ','Euri🤧','+22955497079'),
(398,'Evariste ','Evariste','61173208'),
(399,'Evariste ','Evariste','61173208'),
(400,'Eva💕 ','Eva💕','+22994306085'),
(401,'Eva💕 ','Eva💕','+221772122787'),
(402,'Eyram Gebana','Eyram Gebana','+22896801904'),
(403,'Êzossé ','Êzossé','66208989'),
(404,'Fadi ','Fadi','+22951525556'),
(405,'Fadil🙆🏽🤦🏿‍♂️😩 ','Fadil🙆🏽🤦🏿‍♂️😩','+22953046568'),
(406,'Fafoumi ','Fafoumi','66027236'),
(407,'Fafoumi ','Fafoumi','66027236'),
(408,'Fahardine ','Fahardine','+22951452928'),
(409,'Fahardine Tayewo ','Fahardine Tayewo','+22968595776'),
(410,'Faiseur Chaussures','Faiseur De Chaussures','+22991239054'),
(411,'Fano ','Fano','0171012903'),
(412,'Faridath Osseni','Faridath Osseni','+22969805056'),
(413,'Farihou ','Farihou','+22968000827'),
(414,'Fédération De Volleyball','Fédération Béninoise De Volleyball','+22997941619'),
(415,'Fefe ','Fefe','60498138'),
(416,'Fèmi 🙈💓','Fèmi 🙈💓','+22965236066'),
(417,'Féreol ADR','Féreol ADR.','58071736'),
(418,'Fils de Martine','Fils de Martine','97687756'),
(419,'Fils de Martine','Fils de Martine','97687756'),
(420,'Finan ','Finan','97781879'),
(421,'Finan ','Finan','97781879'),
(422,'Fleur ','Fleur','+22941820473'),
(423,'Flore ','Flore','+22899992900'),
(424,'Floriane SGBH','Floriane SGBH','+22966537544'),
(425,'Fo Ahmed','Fo Ahmed','97743227'),
(426,'Fo Amos','Fo Amos','96178672'),
(427,'Fo Amos','Fo Amos','96178672'),
(428,'Fo Sisi','Fo Sisi','+22967214143'),
(429,'Fofo Joly','Fofo Joly','+1(416)9964400'),
(430,'Fofo Mouidine','Fofo Mouidine','97316078'),
(431,'FOREVER GLORY','FOREVER Elisé GLORY','+22777060216'),
(432,'FOREVER GLORY','FOREVER Elisé GLORY','+22777060216'),
(433,'Françoise ','Françoise','96852206'),
(434,'Françoise ou2','Françoise ou2','96852206'),
(435,'Françoise ou2','Françoise ou2','96852206'),
(436,'Françoise ouè','Françoise ouè','60929357'),
(437,'FrançoisRCI2 ','FrançoisRCI2','+22541511649'),
(438,'FrançoisRCI2 ','FrançoisRCI2','+22541511649'),
(439,'Frédonie✨🌹 ','Frédonie✨🌹','+22969591685'),
(440,'Funaki ','Funaki','67303549'),
(441,'Gabriel ','Gabriel','+22605268819'),
(442,'Gael ','Gaël','54917299'),
(443,'Gael ','Gaël','54917299'),
(444,'Ganiou ','Ganiou','+22996543908'),
(445,'Ganiou IDLK','Ganiou','+22996543908'),
(446,'Garba ','Garba','+22953005873'),
(447,'GB Augustin','GB Augustin','+22999541344'),
(448,'GB Augustin','GB Augustin','+22952027520'),
(449,'GB Augustin','GB Augustin','+2348051782879'),
(450,'Gendarmerie ','Gendarmerie','172'),
(451,'Geoge ','Geoge','67630119'),
(452,'Geoge ','Geoge','67630119'),
(453,'Geoge ','Geoge','67630119'),
(454,'Geoge ','Geoge','67630119'),
(455,'Geoge ','Geoge','67630119'),
(456,'Geoge ','Geoge','67630119'),
(457,'Germain ','Germain','97200352'),
(458,'Germain ','Germain','95615288'),
(459,'Germain ','Germain','97200352'),
(460,'Gideon ','Gideon','99060588'),
(461,'GJos ','GJos','+22952035887'),
(462,'Gloria aka','Gloria aka','61170227'),
(463,'Go ','Go','0788603575'),
(464,'Go  Ennnuie','Go Ennnuie','0702516889'),
(465,'Godson ','Godson','56738167'),
(466,'Godson ','Godson','56738167'),
(467,'GodWill De Keno','GodWill De Keno','51699404'),
(468,'GodWill De Keno','GodWill De Keno','51699404'),
(469,'Goulè ','Goulè','68700409'),
(470,'Goulè ','Goulè','68700409'),
(471,'Goulè ','Goulè','68700409'),
(472,'Goulè ','Goulè','68700409'),
(473,'Goulè ','Goulè','68700409'),
(474,'Goulè ','Goulè','68700409'),
(475,'GOUTON Noé','GOUTON Noé','+22997542851'),
(476,'Gozem ','Gozem','+22994094910'),
(477,'Grace ','Grace','+33780182347'),
(478,'Gracien ','Gracien','0024105203966'),
(479,'Gracien ','Gracien','0024105203966'),
(480,'Grande -sœur','Grande -sœur','63028307'),
(481,'Grande -sœur','Grande -sœur','63028307'),
(482,'Grande soeur','Grande soeur','96788241'),
(483,'Gratien Bénin','Gratien Bénin','95243405'),
(484,'Gratien Bénin','Gratien Bénin','95243405'),
(485,'Grégoire ','Grégoire','0024107346210'),
(486,'Grégoire ','Grégoire','0024107346210'),
(487,'Gros bras','Gros bras','53530292'),
(488,'GUEDENON ','GUEDENON','96993970'),
(489,'Habib ','Habib','+22961169070'),
(490,'Hadilou SIG_FNDA','Hadilou SIG_FNDA','+22962907572'),
(491,'Hélène ADD','Hélène ADD','61657838'),
(492,'Hélène ADD','Hélène ADD','61657838'),
(493,'Hélène Fèmi','Hélène Fèmi','+22954876928'),
(494,'Hélène Fèmi','Hélène Fèmi','+22954876928'),
(495,'Henry 🥷🏿💎','Henry 🥷🏿💎','+22953531300'),
(496,'Honoré ','Honoré','96219278'),
(497,'Honoré ','Honoré','96219278'),
(498,'HOUEDANOU Marius','HOUEDANOU Marius','+22994454555'),
(499,'HOUNKPATI Martin','HOUNKPATI Martin','97196279'),
(500,'Ibrahim  ','Ibrahim','0768830273'),
(501,'Idelphonce ','Idelphonce','0024104331699'),
(502,'Idelphonce ','Idelphonce','0024104331699'),
(503,'Idohou Alaro','Idohou Alaro','97213912'),
(504,'Idohou Alaro','Idohou Alaro','97213912'),
(505,'Ikilimath ','Ikilimath','68287570'),
(506,'Imath🦋 ','Imath🦋','+22997353724'),
(507,' Inconnu 1','Inconnu 1','0022955571323'),
(508,'Inconnu A','Inconnu A','0022999911536'),
(509,'Inconnu A','Inconnu A','0022999911536'),
(510,'Inconnue 1 ','Inconnue 1','55571323'),
(511,'Inda❤️ ','Inda','98255555'),
(512,'Inda❤️ ','Inda','98255555'),
(513,'Innocent ','Innocent','97879646'),
(514,'Iris ','Iris','+22996161665'),
(515,'Isaac DBT ','Isaac DBT','+2250767160701'),
(516,'Isaac Newton😂 ','Isaac Newton😂','+22991134377'),
(517,'Isaac Somé ','Isaac Somé','+2250702770410'),
(518,'Israël Gbh','Israël Gbh','+22952920808'),
(519,'Ivane ','Ivane','0703245263'),
(520,'Iya Habib','Iya Habib','+22966021579'),
(521,'Iya Helène','Iya Helène','63156565'),
(522,'Iya Helène','Iya Helène','63156565'),
(523,'Iya Helène','Iya Helène','63156565'),
(524,'Iya Kefil','Iya Kefil','97089617'),
(525,'Iya Kefil','Iya Kefil','+22897089617'),
(526,'Ja Morant 🏀','Ja Morant 🏀','+22953727969'),
(527,'Jabdelle ','Jabdelle','+22954870043'),
(528,'Jacob SOCAR','Jacob SOCAR','62046182'),
(529,'Jazz Not ','Jazz Not','+22991709999'),
(530,'Jazzygnon ','Jazzygnon','59017971'),
(531,'Jazzygnon ','Jazzygnon','59017971'),
(532,'JB ','JB','40151596'),
(533,'JB ','JB','40151596'),
(534,'Jb ','Jb','62789917'),
(535,'Jean Chauffeur','Jean Chauffeur','+22994293068'),
(536,'Jean Chauffeur','Jean Chauffeur','+22994293068'),
(537,'Jean Luc','Jean Luc','40111110'),
(538,'Jean Luc','Jean Luc','40111110'),
(539,'Jean Paul ','Jean Paul','55197039'),
(540,'Jean Paul ','Jean Paul','+22952820055'),
(541,'Jean Paul ','Jean Paul','55197039'),
(542,'Jean Paul','Jean Paul','+22952820055'),
(543,'Jean ♥️♥️💪🍾🏦❤️🙏🦴','Jean ♥️♥️💪🍾🏦❤️🙏🦴','+33640324766'),
(544,'Jean-jack YAO','Jean-jack YAO','+22994332233'),
(545,'Jeanne IDLK Ciforq','Jeanne IDLK, Secrétaire Ciforq','+22990945373'),
(546,'Jérémy ','Jérémy','67757048'),
(547,'Joachim ','Joachim','60504527'),
(548,'Joachim ','Joachim','60504527'),
(549,'Jocelyne ','Jocelyne','67046450'),
(550,'Joël QUENUM','Joël QUENUM','+22969532374'),
(551,'John ','John','+22967687273'),
(552,'John ','John','+22953726096'),
(553,'John ','John','+22953726096'),
(554,'Jojo 😎💰💲','Jojo 😎💰💲','69078747'),
(555,'Jonathan FNDA','Jonathan FNDA','+22967084056'),
(556,'Jordan Léonely','Jordan Léonely','+22990570543'),
(557,'JOSPIN ','JOSPIN','96759995'),
(558,'Josselin ','Josselin','91328693'),
(559,'Josué AGBOTON','Josué AGBOTON','62931506'),
(560,'Jules Ladekpo','Jules Ladekpo','62629114'),
(561,'Jules Ladekpo','Jules Ladekpo','62629114'),
(562,'Juniel Ague','Juniel Ague','+22969693709'),
(563,'Junior ','Junior','0767475001'),
(564,'Junior École','Junior Auto École','97999069'),
(565,'Juste Chauffeur','Juste Chauffeur','+22995641760'),
(566,'Juste Chauffeur','Juste Chauffeur','+22995641760'),
(567,'Juste Hountonto','Juste Hountonto','+33753273739'),
(568,'Justement 🤣🤣','Justement 🤣🤣','+22940652902'),
(569,'Juste🤣 ','Juste🤣','+22995641760'),
(570,'Juste🤣 ','Juste🤣','+22995641760'),
(571,'Juste🤣 ','Juste🤣','65256552'),
(572,'Juste🤣 ','Juste🤣','+22953971427'),
(573,'Justus AYITOTO','Justus AYITOTO','+22966386463'),
(574,'K-LEADERS ','K-LEADERS','+22996754784'),
(575,'K Providence','K. Providence','+22962328342'),
(576,'Kadi ','Kadi','0704358785'),
(577,' ','Kafil','+22966594525'),
(578,'Kafil ','Kafil','+22966594525'),
(579,'Kanzou-llohi BANKOLE','Kanzou-llohi BANKOLE','+22967772355'),
(580,'Karim ','Karim','97970131'),
(581,'Karim ','Karim','0566913044'),
(582,'Karim ','Karim','97970131'),
(583,'Kebir BELLO','Kebir BELLO','+22954800455'),
(584,'kefilsaoudien ','kefilsaoudien','+22997254799'),
(585,'Kèmi ','Kèmi','62432882'),
(586,'Kenneth DAKO','Kenneth DAKO','95916201'),
(587,'Kenneth Dako ','Kenneth DAKO','95916201'),
(588,'Kenneth Dako ','Kenneth DAKO','+22995955818'),
(589,'Kenneth DAKO','Kenneth DAKO','95916201'),
(590,'Kenneth Gaba','Kenneth Gaba','+22967341587'),
(591,'Keno ','Keno','+22995916201'),
(592,'Kéno ','Kéno','95916201'),
(593,'Kéno ','Kéno','+22995955818'),
(594,'Kéno Dako','Kéno','+22946658245'),
(595,'Kenzy😎 ','Kenzy😎','+22941688888'),
(596,'Kevin Adjato','Kevin Adjato','+22957173699'),
(597,'Kevin Dako','Kevin Dako','+22946658245'),
(598,'Khalil ','Khalil','+2250787067380'),
(599,'KIBA ','KIBA','62256775'),
(600,'KIBA ','KIBA','62256775'),
(601,'Kifa Osseni','Kifa Osseni','+22997099404'),
(602,'Kodir ','Kodir','+22996602098'),
(603,'Koladé ','Koladé','+22959137796'),
(604,'Koladé ','Koladé','+22959137796'),
(605,'Kolade ','Kolade','+22953822026'),
(606,'Kompaore Jo','Kompaore Jo','69777742'),
(607,'Koudous BELLO','Koudous BELLO','+22996742111'),
(608,'KOUMINASSI Mr','KOUMINASSI 2 Mr','+22995784206'),
(609,'KOUMINASSI Mr','KOUMINASSI 2 Mr','+22995784206'),
(610,'L\'américaine😎🤣 ','L\'américaine😎🤣','+22997693334'),
(611,'L\'homme De Santé','L\'homme De Santé','95819398'),
(612,'L\'homme De Santé','L\'homme De Santé','95819398'),
(613,'La 🎧','La Mélo 🎧','+22991394496'),
(614,'Lamidi ','Lamidi','61002135'),
(615,'Lassissi ','Lassissi','64402068'),
(616,'Lassissi ','Lassissi','64402068'),
(617,'Laura ','Laura','0758980029'),
(618,'Lauris Photo','Lauris Photo','97272042'),
(619,'Layandjou ','Layandjou','96317330'),
(620,'Layandjou ','Layandjou','96317330'),
(621,'Layandjou ','Layandjou','96317330'),
(622,'Le RAV4','Le blanc RAV4','53916944'),
(623,' Le Paul','Le Paul','+22503476751'),
(624,'Le 🔥😎','Le Peace 🔥😎','+22942082957'),
(625,' Le Zins','Le Zins','50085206'),
(626,' Le Zins','Le Zins','50085206'),
(627,'Leo ','Leo','+22965648871'),
(628,'LEO UBA','LEO UBA','61191924'),
(629,'LEO UBA','LEO UBA','61191924'),
(630,'Léonardo ','Léonardo','+22959019712'),
(631,'Lerac ','Lerac','51125527'),
(632,'Liboire Ciforq','Liboire Ciforq','+22997572762'),
(633,'Liboire Ciforq','Liboire Ciforq','+22997572762'),
(634,'Lisboa Léonce','Lisboa Léonce','+22966375648'),
(635,'Livreur 1','Livreur 1','+22997705652'),
(636,'Lkt🥃🚬 ','Lkt🥃🚬','+22953535121'),
(637,'Loane ','Loane','63055002'),
(638,'Louis ZOUNON','Louis ZOUNON','+33752742645'),
(639,'Louis ZOUNON','Louis ZOUNON','+33752742645'),
(640,'lucresse ','lucresse','+22956657728'),
(641,'M Unknown ','M Unknown','+22960870606'),
(642,'M DASILVA','M. DASILVA','+22997001939'),
(643,'Madame AIHOU','Madame AIHOU','97163966'),
(644,'Mafoya ','Mafoya','+22954975913'),
(645,'Maître Charpentier - Eugène','Maître Charpentier - Tonton Eugène','+22997558871'),
(646,'Maman ','Maman','+22961630008'),
(647,'Maman Andy ','Maman Andy','+25762873953'),
(648,'Maman D','Maman Andy D','+25762873953'),
(649,'Maman Arie 🙃','Maman Arie 🙃','+22968836515'),
(650,'Maman Bola','Maman Bola','40748713'),
(651,'Maman Cherif','Maman Cherif','97089617'),
(652,'Maman Cherif','Maman Cherif','97089617'),
(653,'Maman Fenou','Maman Fenou','+22967239749'),
(654,'Maman Hélène','Maman Hélène','96908292'),
(655,'Maman Hélène','Maman Hélène','63156565'),
(656,'Maman Hélène','Maman Hélène','63156565'),
(657,'Maman Israël','Maman Israël','96080808'),
(658,'Maman Israël','Maman Israël','96080808'),
(659,'Maman Koko','Maman Koko','66674154'),
(660,'Maman Koko','Maman Koko','66674154'),
(661,'Maman Somad','Maman Somad','96500969'),
(662,'Maman Somad','Maman Somad','96500969'),
(663,'Maman Watsup','Maman Watsup','+22960507205'),
(664,'Maman Watsup','Maman Watsup','+22960507205'),
(665,'Maman 🌻 ','Maman 🌻','+22891056003'),
(666,'Maman 🌻 (Suni)','Maman 🌻 GEBANA (Suni)','+22898767848'),
(667,'Maman 🧡','Maman 🧡','+22899801610'),
(668,'Maman 🧡','Maman 🧡','+22899801610'),
(669,'Maman 🧡','Maman 🧡','99801610'),
(670,'Maman🌻 ','Maman🌻','+22891056003'),
(671,'Maman🌻 🌻','Maman🌻','+22891056003'),
(672,'Maman🌻 🌻','Maman🌻','+22963206648'),
(673,'Manager ','Manager','52810404'),
(674,'Manager ','Manager','52810404'),
(675,'Manager ','Manager','+22991021650'),
(676,'Mannette Seller','Mannette Seller','9210'),
(677,'MANNETTE Seller','MANNETTE Seller','+22892102129'),
(678,'Mannette Seller','Mannette Seller','9210'),
(679,'Manu😎 ','Manu😎','+2250594558187'),
(680,'Marc ','Marc','+22958897720'),
(681,'Marcel Imprimeur','Marcel Imprimeur','66968182'),
(682,'Marcel☄️ ','Marcel☄️','+22946029937'),
(683,'Marcel☄️ ','Marcel☄️','+22946029937'),
(684,'Marco 📚','Marco 📚','+22995711371'),
(685,'Mardochée Electro-tech','Mardochée Electro-tech','97306817'),
(686,'Mariam OCENI','Mariam OCENI','+22502616176'),
(687,'Mariane Tognibo','Mariane Tognibo','63980226'),
(688,'Mariane Tognibo','Mariane Tognibo','63980226'),
(689,'Marie A','Marie A','+22954232317'),
(690,'MARIE ROSE','MARIE ROSE','61749101'),
(691,'Marie 💙🙂','Marie 💙🙂','+22969076024'),
(692,'Marielle 2','Marielle 2','+22951509600'),
(693,'Maroufou ','Maroufou','97087701'),
(694,'Maroufou ','Maroufou','97087701'),
(695,'Marryse 😆','Marryse 😆','+22953046867'),
(696,'Martine ','Martine','66193704'),
(697,'Martine ','Martine','66193704'),
(698,'Marwhanne ','Marwhanne','+22960603838'),
(699,'Maryse GAHOU FNDA)','Maryse GAHOU (SIG FNDA)','+22967764319'),
(700,'Master N','Master N','+25762878856'),
(701,'Matelassier ','Matelassier','97256480'),
(702,'Mathieu ','Mathieu','+22892855872'),
(703,'Maurel ','Maurel','074828813'),
(704,'Maurice CELTIS ','Maurice CELTIS','+22944266825'),
(705,'Maya ','Maya','+22999999979'),
(706,'Mehdi ','Mehdi','+22967777777'),
(707,'Mélissa😌 ','Mélissa😌','+22998910444'),
(708,'Mémé ','Mémé','94086884'),
(709,'Mémé ','Mémé','+22999996114'),
(710,'Mémé ','Mémé','+22999996114'),
(711,'Mémé ','Mémé','40547718'),
(712,'Mémé ','Mémé','40547718'),
(713,'Mémé ','Mémé','+22943666619'),
(714,'Mémé ','Mémé','94086884'),
(715,'Mémé Bernadette','Mémé Bernadette','62406100'),
(716,'Mémé Bernadette','Mémé Bernadette','62406100'),
(717,'Mémé Porto','Mémé Porto','+22967979398'),
(718,'Messi ','Messi','+2250702874643'),
(719,'Micha ','Micha','+22953200802'),
(720,'Mina🤣 ','Mina🤣','+22953097932'),
(721,'Mina🤣 ','Mina🤣','53097932'),
(722,'Mitu Tres Bien Paiyer ','Mitu Tres Bien Paiyer','05941766170'),
(723,'Mme SeSie','Mme EDDIE SeSie','55047010'),
(724,'Mn boss MATRIX ','Mn boss MATRIX','66871893'),
(725,'Modeste Biokou','Modeste Biokou','0301202008'),
(726,'Modeste Biokou','Modeste Biokou','0301202008'),
(727,'Modeste Biokou','Modeste Biokou','+32483269838'),
(728,'Mohamed 💰👑','Mohamed MCK 💰👑','+22961771793'),
(729,'Mohamed MCK💰👑','Mohamed MCK💰👑','+22961771793'),
(730,'Moi Togo','Moi Moov Togo','+22899330686'),
(731,'Moise ','Moise','0709408638'),
(732,'Momo ','Momo','0700017074'),
(733,'Momo ','Momo','53428601'),
(734,'Mom❤️ ','Mom❤️','+33749982125'),
(735,'Moov Benin','Moov Benin','+22955505050'),
(736,'Moov Benin','Moov Benin','+22955505050'),
(737,'Moov Modem','Moov Bénin Modem','55276010'),
(738,'Moov Modem','Moov Bénin Modem','55276010'),
(739,'Morgy ','Morgy','+22962465353'),
(740,'Morvan ','Morvan','+22955339796'),
(741,'Moubine MOUTAITOU','Moubine MOUTAITOU','+22997136665'),
(742,'Mouhamèd fifadji','Mouhamèd fifadji','53190273'),
(743,' Benito','Mr Benito','+22995667116'),
(744,' Denis','Mr Denis','97863098'),
(745,' Denis','Mr Denis','97863098'),
(746,'Elysée Auto-école','Mr Elysée Auto-école','+22996463056'),
(747,'Elysée Auto-école','Mr Elysée Auto-école','+22967252525'),
(748,' François','Mr François','97499706'),
(749,' Joseph','Mr Joseph','+22890372640'),
(750,' KOUMINASSI','Mr KOUMINASSI','97498504'),
(751,' KOUMINASSI','Mr KOUMINASSI','97498504'),
(752,'Luc Gebana','Mr Luc Loco Gebana','+22897131334'),
(753,'Luc Gebana','Mr Luc Loco Gebana','+22997131334'),
(754,' Magloire','Mr Magloire','63003736'),
(755,' Mardochée','Mr Mardochée','+22961831420'),
(756,'Nonfon Junior','Mr Nonfon Junior','+233240582093'),
(757,' Reneau','Mr Reneau','96911431'),
(758,'TEVI Armand','Mr TEVI Armand','+22890860831'),
(759,' Willis','Mr. Willis','66361272'),
(760,'MTN BJ','MTN BJ','62620000'),
(761,'MTN BJ','MTN BJ','61000000'),
(762,'murmur ','murmur','+22968366921'),
(763,'murmur ','murmur','+22968366921'),
(764,'Mussolini ','Mussolini','+22969977882'),
(765,'M❤️m ','M❤️m','+33749982125'),
(766,'Nabylzer😎🏀 ','Nabylzer😎🏀','+22941276142'),
(767,'Nabyl🏀 ','Nabyl🏀','+22941276142'),
(768,'Nanette Gs','Nanette Gs','+22997751157'),
(769,'Naofal ','Naofal','90647210'),
(770,'Naofal 🐍🔥💰','Naofal 🐍🔥💰','+22955725549'),
(771,'Naomi😆 ','Naomi😆','98162573'),
(772,'Nimat ','Nimat','90160450'),
(773,'Noéli Maman','Noéli Maman','66239930'),
(774,'Noéli Maman','Noéli Maman','66239930'),
(775,'Nouatchi Dorian','Nouatchi Dorian','+22940730882'),
(776,'Nouriath ','Nouriath','46408633'),
(777,'Nouriath ','Nouriath','46408633'),
(778,'Num0 ','Num0','97627906'),
(779,'Num0 ','Num0','97627906'),
(780,'O tacosi','O tacosi, v','+22999936512'),
(781,'Odette Sodinyessi','Odette Sodinyessi','+22997394579'),
(782,'Odette Sodinyessi','Odette Sodinyessi','+22997394579'),
(783,'Okiki ','Okiki','66506822'),
(784,'Okiki ','Okiki','66506822'),
(785,'Olä Pictures','Olä Pictures','+22961800822'),
(786,'Olgaa 🙃','Olgaa 🙃','+33646266733'),
(787,'Olivier  ','Olivier','+32485699289'),
(788,'Olori ','Olori','66401757'),
(789,'Olori ','Olori','66401757'),
(790,'Oó8777 Poo 777777⁷ I\'ll O 6666666i6i6ó⁶p','Oó8777 Poo 777777⁷ I\'ll O Oops 6666666i6i6ó⁶p','#'),
(791,'Ordi Ocam','Ordi Ocam','+22890045876'),
(792,'Oriiii🥹 ','Oriiii🥹','+22940733476'),
(793,'Ornel ','Ornel','+22952294829'),
(794,'Ornella ','Ornella','0704381864'),
(795,'Ornella  ','Ornella','0564915241'),
(796,'OTR ','OTR','8280'),
(797,'OTR ','OTR','8280'),
(798,'Ounfath Rhm 💞🌹','Ounfath Rhm 💞🌹','+22951542921'),
(799,'Ounfath Rhm 💞🌹','Ounfath Rhm 💞🌹','+22951542921'),
(800,'Ourma léwé','Ourma léwé','53015781'),
(801,'Ousman Dessinateur','Ousman GBADAMASSU Dessinateur','+22997833326'),
(802,'Owennn ','Owennn','+22968729314'),
(803,'Padonou Sêdjro','Padonou Sêdjro','+33745321836'),
(804,'Padonou Sêdjro','Padonou Sêdjro','+33676382014'),
(805,'Pancrace😎🔥 ','Pancrace😎🔥','+22994004739'),
(806,'Paolus ','Paolus','+22969255656'),
(807,'Papa ','Papa','0555143756'),
(808,'Papa ','Papa','96317330'),
(809,'Papa ','Papa','+22997695555'),
(810,'Papa ','Papa','96317330'),
(811,'Papa ','Papa','96317330'),
(812,'Papa ','Papa','97695555'),
(813,'Papa CB Charbel','Papa CB Houngbo Charbel','+22997414644'),
(814,'Papa Lorinda','Papa Lorinda','96344660'),
(815,'Parfait ','Parfait','+22997820588'),
(816,'Pascal ','Pascal','60676367'),
(817,'Patricia  ','Patricia','0707687137'),
(818,'Paul Biokou','Paul Biokou','51543424'),
(819,'Paul Biokou','Paul Biokou','63336876'),
(820,'Paul Biokou','Paul Biokou','51543424'),
(821,'Paul Biokou','Paul Biokou','51543424'),
(822,'Paul Biokou','Paul Biokou','63336876'),
(823,'Paulin Biokou','Paulin Biokou','69255719'),
(824,'Paulin Biokou','Paulin Biokou','69255719'),
(825,'Pct Prof','Pct Prof','+22997069707'),
(826,'PDG Couture','PDG Zim Couture','+22965090745'),
(827,'Pénaldo EPT','Pénaldo EPT','+22996335201'),
(828,'Pépé ','Pépé','+22965249285'),
(829,'Pépé (M)','Pépé (M)','96673779'),
(830,'Pépé (M)','Pépé (M)','96673779'),
(831,'Pétass ','Pétass','+22968595776'),
(832,'Peter ','Peter','53827999'),
(833,'Petit 2','Petit Roger 2','65141415'),
(834,'Petit 2','Petit Roger 2','65141415'),
(835,'Phano🥂✨🍀 ','Phano🥂✨🍀','+22951022023'),
(836,'PHANTOM👻☠️☘️🤲🏻❤️🙏🏻🍫✝️️ ','PHANTOM👻☠️☘️🤲🏻❤️🙏🏻🍫✝️⭐️','+2250759155790'),
(837,'Philomène Tohibo','Philomène Tohibo','0022899621967'),
(838,'Philomène Tohibo','Philomène Tohibo','0022899621967'),
(839,'Photo Pass/smatch','Photo Pass/smatch','+22955709261'),
(840,'Pio ','Pio','+22953219570'),
(841,'Pistolet ','Pistolet','+24105439956'),
(842,'Pistolet ','Pistolet','+24105439956'),
(843,'Police ','Police','117'),
(844,'Police ','Police','117'),
(845,'Pr KINMISSEDO','Pr. Jérôme KINMISSEDO','+22966759672'),
(846,'Pr Keno','Pr. Maths Keno','97520635'),
(847,'Pr ASSOUMA','Pr. Mohamed ASSOUMA','+22995526066'),
(848,'Primous ','Primous','94384161'),
(849,'Primous ','Primous','94384161'),
(850,'Prince  ','Prince','0798497671'),
(851,'Prince D','Prince D','61208150'),
(852,'Prince 🌹❤️','Prince D. 🌹❤️','+22965252677'),
(853,'Princia abokicodji','Princia abokicodji','66226947'),
(854,'Printys🫠 🥷🏿','Printys🫠 🥷🏿','+22990571000'),
(855,' Anglais','Prof Anglais','97491073'),
(856,' Anglais','Prof Anglais','+22961535325'),
(857,' Graphisme','Prof Graphisme','67366867'),
(858,'Prospère ','Prospère','+24106604971'),
(859,'Prospère ','Prospère','+24106604971'),
(860,'Prospère ','Prospère','+24106604971'),
(861,'Providence ','Providence','+22990756682'),
(862,'Providence ','Providence','+22990756682'),
(863,'Puk Togo','Puk Moov Togo','27321987'),
(864,'Puk Togo','Puk Moov Togo','27321987'),
(865,'Rachelle ','Rachelle','95559264'),
(866,'Rachelle ','Rachelle','95559264'),
(867,'Raidath 🙃','Raidath 🙃','+22995188466'),
(868,'Randy🩸 ','Randy🩸','+22969078826'),
(869,'Raphaëlla🦋 ','Raphaëlla🦋','+22990733969'),
(870,'Rapha🐉🩷🦋 ','Rapha🐉🩷🦋','+22990733969'),
(871,'Rasack ','Rasack','95036306'),
(872,'Rasack ','Rasack','95036306'),
(873,'Ray Ague','Ray Ague','+22960932967'),
(874,'Rayou ','Rayou','+22953631683'),
(875,'Rebecca Amehi Eau Lome','Rebecca Amehi Eau Pure Lome','+22899207316'),
(876,'Rebecca 🤣😭','Rebecca 🤣😭','+22941742807'),
(877,'Reine ','Reine','0702661695'),
(878,'René ','René','67272879'),
(879,'Rhino😎 ','Rhino😎','+22960160202'),
(880,'Ricardo ADDR','Ricardo ADDR','68740850'),
(881,'Ricardo ADDR','Ricardo ADDR','68740850'),
(882,'Ridoine ','Ridoine','+22996960693'),
(883,'Rio 🤧','Rio Pop 🤧','+22996923547'),
(884,'Ro ','Ro','+22990211249'),
(885,'Robert ','Robert','97271196'),
(886,'Robert ','Robert','97271196'),
(887,'Robert 2','Robert 2','60122139'),
(888,'Robert 2','Robert 2','60122139'),
(889,'Rodrigue Noblesse','Rodrigue Noblesse','97100249'),
(890,'Rodrigue Noblesse','Rodrigue Noblesse','97100249'),
(891,'Rodrigue OGOUTCHORO','Rodrigue OGOUTCHORO','+22966297503'),
(892,'Rodrigue OGOUTCHORO','Rodrigue OGOUTCHORO','68458174'),
(893,'Roméo AGD','Roméo AGD','+22966428701'),
(894,'Romuald AMDJ','Romuald AMDJ','90882085'),
(895,'Romuald AMDJ ','Romuald AMDJ','90882085'),
(896,'Romuald SOS','Romuald SOS','+22961076819'),
(897,'Romuald SOS ','Romuald SOS','+22961076819'),
(898,'Rosemonde ','Rosemonde','91323519'),
(899,'Rotimi 2','Rotimi 2','96462886'),
(900,'Rotimi 2','Rotimi 2','96462886'),
(901,'Roxy🔥 ','Roxy🔥','+22964230711'),
(902,'Rudy ','Rudy','+22952009015'),
(903,'Saka Couture','Saka Couture','+22995203323'),
(904,'Saka Couture','Saka Couture','+22997089942'),
(905,'Salako Emmanuel','Salako Emmanuel','62867227'),
(906,'Saliou ','Saliou','97555413'),
(907,'Saliou ','Saliou','97555413'),
(908,'Samir ','Samir','0768547999'),
(909,'Samira  ','Samira','0102810639'),
(910,'Samira  ','Samira','0102810636'),
(911,'Samuel ','Samuel','+22954233106'),
(912,'Samuel ','Samuel','+22954233106'),
(913,'Samy ','Samy','99524699'),
(914,'Samy ','Samy','99524699'),
(915,'Santé ','Santé','800'),
(916,'Santé ','Santé','800'),
(917,'Saoud Juicy','Saoud Juicy','+22964902896'),
(918,'Saoud Juicy','Saoud Juicy','+22964902896'),
(919,'Sapeurs-Pompiers ','Sapeurs-Pompiers','118'),
(920,'Sapeurs-Pompiers ','Sapeurs-Pompiers','118'),
(921,'Ulrich Chaussure','SDF Ulrich Chaussure','66946783'),
(922,'Sécu Is','Sécu Is','+22996888697'),
(923,'Sèdjro ','Sèdjro','+22997886245'),
(924,'Seraphine ','Seraphine','97967577'),
(925,'Seraphine ','Seraphine','97967577'),
(926,'Séréna ','Séréna','+22990544570'),
(927,'Serge ','Serge','+22962147915'),
(928,'Serge ','Serge','+22962147915'),
(929,'Serge ','Serge','+22995695634'),
(930,'Serge 💝','Serge 💝','+22962147915'),
(931,'Serge 💝','Serge 💝','+22962147915'),
(932,'Sergio😎 ','Sergio😎','+22962147915'),
(933,'Service 1','Service Client 1','777'),
(934,'Service 1','Service Client 1','777'),
(935,'Service 2','Service Client 2','+22899997777'),
(936,'Service 2','Service Client 2','+22899997777'),
(937,'Séverin ','Séverin','53946320'),
(938,'Séverin ','Séverin','53946320'),
(939,'Sidi ','Sidi','60274106'),
(940,'Sidi ','Sidi','60274106'),
(941,'Sidonie BNAD','Sidonie BNAD','+22962582917'),
(942,'Sidonie BNAD','Sidonie BNAD','+22962582917'),
(943,'Sipe ','Sipe','+24105415729'),
(944,'Sipe ','Sipe','+24105415729'),
(945,'Sipe1 ','Sipe1','0024107418707'),
(946,'Sipe1 ','Sipe1','0024107418707'),
(947,'Sista❤️ ','Sista❤️','+33658514020'),
(948,'SmartLand Calavi','SmartLand Calavi','69002928'),
(949,'Solo ','Solo','+2250747218387'),
(950,'Somad ','Somad','69259170'),
(951,'Soubérou ','Soubérou','+4915145452440'),
(952,'Soubérou ','Soubérou','+4915145452440'),
(953,'Soûlé Volley','Soûlé Volley','+22997279414'),
(954,'Soûlé Volley','Soûlé Volley','+22997279414'),
(955,'Sourou ','Sourou','+22997140697'),
(956,'Stagiaire Ciforq','Stagiaire Ciforq','69357601'),
(957,'Stagiaire Ciforq','Stagiaire Ciforq','69357601'),
(958,'Stéphane (Ashmed)','Stéphane (Ashmed)','+22964398098'),
(959,'STÉPHANE FNDA)','STÉPHANE (SIG FNDA)','+22967710659'),
(960,'Stéphane Lyon🌹🥰','Stéphane Lyon🌹🥰','+22994780710'),
(961,'S. ','Stéphane S.','97514381'),
(962,'Steve Job😆','Steve Job😆','+23324189116'),
(963,'Steve Job😆','Steve Job😆','+233241891116'),
(964,'Sweety😌💍🔥 ','Sweety😌💍🔥','+22951254050'),
(965,'Sylvio ','Sylvio','+22961030110'),
(966,'Syrie ','Syrie','62181701'),
(967,'Ta Raissa','Ta Raissa🍜😌','+22967213205'),
(968,'Ta Raissa','Ta Raissa🍜😌','+22670166606'),
(969,'Tante Sabine','Tante Sabine','62459242'),
(970,'Tantie Cynthia ','Tantie Cynthia','0749193348'),
(971,'Tantie 🙃','Tantie Prisci 🙃','+22997395106'),
(972,'Tata Martine','Tata Martine','+22995819313'),
(973,'Tata Martine','Tata Martine','+22997479945'),
(974,'Tata mouhamèd','Tata mouhamèd','60774745'),
(975,'Taxi ','Taxi','66524664'),
(976,'Taxi ','Taxi','66524664'),
(977,'TCHOBO Stéphane','TCHOBO Stéphane','+22996380914'),
(978,'TCHOBO Stéphane ','TCHOBO Stéphane','+22996380914'),
(979,'Teo Midcoin','Teo Midcoin','+22967120944'),
(980,'Thomas ','Thomas','61513429'),
(981,'Thomas Arnaqueur a stopper','Thomas Arnaqueur a stopper','+33752537366'),
(982,'Ti-shan🙂 ','Ti-shan🙂','+22991008383'),
(983,'TIDJANI Habib','TIDJANI Habib','+22966069210'),
(984,'Togni ','Togni','67513669'),
(985,'Togni ','Togni','67513669'),
(986,'Togolo😂🙃 ','Togolo😂🙃','+22991533232'),
(987,'Tokpè bac','Tokpè bac','62069997'),
(988,'Tonton 😎','Tonton Anselme 😎','+22994020262'),
(989,'Tonton Ben','Tonton Ben','63234545'),
(990,'Tonton Crypto😎😎','Tonton Crypto😎😎','+22999346969'),
(991,'Tonton Emile','Tonton Emile','+22995873959'),
(992,'Tonton Eugène ','Tonton Eugène','97991685'),
(993,'Tonton Gentil ','Tonton Gentil','+22991210596'),
(994,'Tonton Honoré','Tonton Honoré','90906016'),
(995,'TOSSOU Pamela','TOSSOU Pamela','+22951828221'),
(996,'TOSSOU Pamela','TOSSOU Pamela','+22951828221'),
(997,'Toundé 1','Toundé 1','65201083'),
(998,'Toundé 1','Toundé 1','65201083'),
(999,'Touré🤑🤐 ','Touré🤑🤐','+224612343847'),
(1000,'TV Fixer','TV Fixer','+22890093248'),
(1001,'Ulrich Owanga','Ulrich Owanga','+22997606781'),
(1002,'Unknown  ','Unknown','96224771'),
(1003,'Unknown Egypt','Unknown Egypt','+20224803584'),
(1004,'Uriello Francisco🏀🏐','Uriello Francisco🏀🏐','+22965313132'),
(1005,'V Plombier','V Plombier','95193698'),
(1006,'Vendeur Informatique','Vendeur D\'accessoires Informatique','+22890169626'),
(1007,'Vendeur Matelas','Vendeur Matelas','58387165'),
(1008,'Vendeur Matelas','Vendeur Matelas','58387165'),
(1009,'Victoria (Suni) ','Victoria (Suni)','+22892300650'),
(1010,'VMS ','VMS','444'),
(1011,'VMS ','VMS','444'),
(1012,'Waliou Epouse','Waliou Epouse','61971268'),
(1013,'Waliou Epouse','Waliou Epouse','61971268'),
(1014,'Warren ','Warren','+22941140440'),
(1015,'Wave retapé','Wave retapé','61106774'),
(1016,'Weev ','Weev','97095033'),
(1017,'Welcome Africa','Welcome Africa','+22963184045'),
(1018,'Whalli ','Whalli','53069368'),
(1019,'Wifi Sim','Wifi Sim','+22962932116'),
(1020,'Willy\'s Moov','Willy\'s Moov','95800833'),
(1021,'Wilmy💍❤️🙃 ','Wilmy🙃','60702450'),
(1022,'Wince ','Wince','40174107'),
(1023,'Wince ','Wince','+22998134199'),
(1024,'Wince A','Wince A.','96112247'),
(1025,'Yachir ADEKOLA','Yachir ADEKOLA, Gamzo','+22967957475'),
(1026,'Yachir ADEKOLA','Yachir ADEKOLA, Gamzo','+22967957475'),
(1027,'Yachir ADK','Yachir ADK','+22967125541'),
(1028,'Yacoubou ','Yacoubou','63427548'),
(1029,'Yacoubou ','Yacoubou','63427548'),
(1030,'Yannick ','Yannick','0767006175'),
(1031,'Yasmine L\'étoile','Yasmine L\'étoile','62063824'),
(1032,'youpilab com','youpilab com','+233240582093'),
(1033,'youpilab com','youpilab com','+233240582093'),
(1034,'Yvan  ','Yvan','0701047310'),
(1035,'Yves Amoussougbo','Yves Amoussougbo','+2250576938358'),
(1036,'Yves Amoussougbo','Yves Amoussougbo','+2250576938358'),
(1037,'Yvon ','Yvon','61280267'),
(1038,'Yvon ','Yvon','61280267'),
(1039,'Zabilon ','Zabilon','66679968'),
(1040,'Zakary Menusier ','Zakary Menusier','+22995469437'),
(1041,'Zem Ciforq','Zem Chauffeur Ciforq','97280367'),
(1042,'Zinsalo21 ','Zinsalo21','+22969781310'),
(1043,'Zoul Kif','Zoul Kif','+22997181512'),
(1044,'Zull Kif ','Zull Kif','+22997181512'),
(1045,'스더 에','에스더','+22991793810'),
(1046,' ','에스더','+22991793810'),
(1047,'2 Charo','2 Charo','+22954101696'),
(1048,'41 27 18','41 27 43 18','41274318'),
(1049,' ','41 27 43 18','41274318'),
(1050,'58 91 60','58 91 21 60','58912160'),
(1051,' ','58 91 21 60','58912160'),
(1052,'62069997 ','62069997','62069997'),
(1053,' ','62069997','62069997'),
(1054,' ','62069997','62069997'),
(1055,' ','96 84 73 90','96847390'),
(1056,'96 84 90','96 84 73 90','96847390'),
(1057,' ','99 24 84 83','99248483'),
(1058,'99 24 83','99 24 84 83','99248483'),
(1059,' ','99858484','99858484'),
(1060,' ','99858484','99858484'),
(1061,' ','*','*'),
(1062,'+229 60 14 88 00 ','+229 60 14 88 00','+22960148800'),
(1063,' ','+22960148800','+22960148800'),
(1064,' ','+22960148800','+22960148800'),
(1065,' ','+22995992930','+22995992930'),
(1066,'+33 6 33 69 21','+33 6 33 69 60 21','+33633696021'),
(1067,' ','+33633696021','+33633696021'),
(1068,' ','+33633696021','+33633696021'),
(1069,'‎Emmanuel Cosit','‎Emmanuel Cosit','+22952176227'),
(1070,'❤️‍🔥NO MERCY ONLY Saga','❤️‍🔥NO MERCY ONLY SAVAGERY❤️‍🔥 Saga','+22990191994'),
(1071,'A ','A','0022962786299'),
(1072,'Abdel Market','Abdel Market','+22995443156'),
(1073,'Abdel 😌','Abdel 😌','+22990180335'),
(1074,'Aboubacar ','Aboubacar','98111966'),
(1075,'Achille ','Achille','60558160'),
(1076,'Adebo ','Adebo','69277177'),
(1077,'Adéléké 🦋✨','Adéléké 🦋✨','+22962797515'),
(1078,'Adeyemi ⚡','Adeyemi ⚡','+22962464070'),
(1079,'Adinane😊 ','Adinane😊','91923021'),
(1080,'Administration Sonou','Administration bourse Sonou','97180342'),
(1081,'Adonis 🔥','Adonis 🔥','+22991741117'),
(1082,'Aguégué ','Aguégué','67008036'),
(1083,'Ahmad Shittu','Ahmad Akambi Shittu','+22969723776'),
(1084,'Aimé ','Aimé','+22967159502'),
(1085,'Aimé ','Aimé','64697538'),
(1086,'Akouègnon BOGNON','Akouègnon Ghislain BOGNON','+33753034522'),
(1087,'Albéric ⚡','Albéric ⚡','+22951819652'),
(1088,'Alexandre ','Alexandre','+22962434807'),
(1089,'Alexandre K','Alexandre K','97674714'),
(1090,'Amédée ','Amédée','+22991817281'),
(1091,'Amidath🤗♥️🍯 ','Amidath🤗♥️🍯','+22945636827'),
(1092,'Amour ','Amour','+22997660330'),
(1093,'Amour Patinvoh','Amour Patinvoh','+22998363735'),
(1094,'Anaïs💖 ','Anaïs💖','69718666'),
(1095,'Ando ','Ando','+22961319860'),
(1096,'Anicet& Anicette','Anicet& Anicette','+22994896160'),
(1097,'Anry✨🔥 ','Anry✨🔥','+22996553983'),
(1098,'Arcaduis 🤐😵😾☠️☠️','Arcaduis 🤐😵😾☠️☠️','+22967739271'),
(1099,'Ari 💌','Ari Stide 💌','+22966439492'),
(1100,'Ariane ❤️✨','Ariane ❤️✨','+22990143929'),
(1101,'Arielle 😇','Arielle Charonne 😇','+22958376399'),
(1102,'Arielle D🤗♥️🚶🏾‍♀️','Arielle D🤗♥️🚶🏾‍♀️','+22967013902'),
(1103,'ARINLOYE💖💕 ','ARINLOYE💖💕','+22956523602'),
(1104,'Armand 🙄','Armand 🙄','+22962779580'),
(1105,'Ashley ','Ashley','+237696725054'),
(1106,'Augustin ASSOGBA','Augustin AGOSSOU ASSOGBA','+22996452146'),
(1107,'Auleris ⚡','Auleris ⚡','+22962277837'),
(1108,'ANAÏS☺️♎ ','A~N~A~Ï~S~☺️♎','+22995036519'),
(1109,'Bachirou ','Bachirou','+22962746113'),
(1110,'BAMIBOTCHE A. 💕','BAMIBOTCHE A. Augustin 💕','+22962673025'),
(1111,'Be Winner 😉 ','Be Winner 😉','+22954155934'),
(1112,'bedel ','bedel','+22966537913'),
(1113,'Belac😇 ','Belac😇','+22999830971'),
(1114,'Ben ⚡','Ben ⚡','+22656765643'),
(1115,'Bhrayane Ade','Bhrayane Smart Ade','+22990205486'),
(1116,'Blevenec ','Blevenec','+22967344795'),
(1117,'Boluwatifè🤚🚶‍♂️ ','Boluwatifè🤚🚶‍♂️','+22969615987'),
(1118,'Boulot  ','Boulot','22955290594'),
(1119,'Brice ','Brice','96891804'),
(1120,'Brice A','Brice A','66999293'),
(1121,'Bronze ⚡','Bronze ⚡','+22961645527'),
(1122,'Camus☺️ ','Camus☺️','+22961390097'),
(1123,'CashService Bénin ','CashService Bénin','+22968555015'),
(1124,'CDOSSA ♥️🦋','CDOSSA ♥️🦋','+22782634594'),
(1125,'Charles Maurice','Charles Maurice','+22969716065'),
(1126,'Charlie. Je Te 🚶🏾‍♀️🙄','Charlie...Je Te Kiffais 🚶🏾‍♀️🙄','+22966654976'),
(1127,'CHEZ DIANO','CHEZ DIANO','+22951107289'),
(1128,'Christ N’guessan','Christ N’guessan','+2250555192367'),
(1129,'Christian ⚡','Christian ⚡','+22509769424'),
(1130,'christiang ','christiang','+22991196015'),
(1131,'Christine ','Christine','51476239'),
(1132,'Christophe 🥰♥️','Christophe H.🥰♥️','+22967479002'),
(1133,'Christophe 🦋🥰','Christophe 🦋🥰','97514205'),
(1134,'Christophe 🦋🥰','Christophe 🦋🥰','+22999081628'),
(1135,'Citoyen du Bienvenue','Citoyen du monde Bienvenue','+22954272013'),
(1136,'Claire ','Claire','61056426'),
(1137,'Clé Business','Clé Business','+22953299989'),
(1138,'Conceptio ⚡✨🌚','Conceptio ⚡✨🌚','+22953333690'),
(1139,'Constant 🌚🙂','Constant 🌚🙂','+22990238088'),
(1140,'Costume ','Costume','+22952350480'),
(1141,'Costume 2','Costume 2','+22999029497'),
(1142,'Coupon ','Coupon','99383469'),
(1143,'Crédo FANOU','Crédo FANOU','+33781576380'),
(1144,'Crédo 😌🖖','Crédo Roselin 😌🖖','+22962102871'),
(1145,'Crédo 🚶🏾‍♀️😎','Crédo 🚶🏾‍♀️😎','+22956180838'),
(1146,'Crispus 💜','Crispus 💜','+22961163145'),
(1147,'Crispus 🔋🖇️','Crispus 🔋🖇️','+22966654574'),
(1148,'Dada ','Dada','52249554'),
(1149,'Damien ','Damien','+22961272389'),
(1150,'Damtchena ','Damtchena','+22997586596'),
(1151,'Daulvanie ','Daulvanie','+22961596173'),
(1152,'Dayane ','Dayane','+22961792065'),
(1153,'De-Christ ','De-Christ','+22996481758'),
(1154,'Dembouz ','Dembouz','90937244'),
(1155,'Déo☺️ ','Déo☺️','+22991922848'),
(1156,'Depot 1xbet','Depot 1xbet','96748669'),
(1157,'Digit ','Digit','62939393'),
(1158,'Dital 😒','Dital 😒','+22962161323'),
(1159,'Donald 😇😊🥰','Donald 😇😊🥰','69428020'),
(1160,'dossoud ','dossoud','+22966670261'),
(1161,'DouAd✨🌚🚶🏾‍♀️ ','DouAd✨🌚🚶🏾‍♀️','+22967306997'),
(1162,'Dressur Assistance ✅ ','Dressur Assistance ✅','+22964044294'),
(1163,'Edmond V','Edmond V','96171002'),
(1164,'Eliasard ','Eliasard','+22966313807'),
(1165,'Enfant Gilbert','Enfant Gilbert','97554179'),
(1166,'Epiphane 🙄😐😒😪🥴👹🎃','Epiphane 🙄😐😒😪🥴👹🎃','+22969944470'),
(1167,'Erwin ⚡','Erwin ⚡','+22961403390'),
(1168,'Espoir ','Espoir','+22953584970'),
(1169,'Evelyne 😌','Evelyne 😌','62277479'),
(1170,'Fabrice ','Fabrice','+22952538361'),
(1171,'Fati❤️🤍 ','Fati❤️🤍','+22996336696'),
(1172,'Fernand FAGNIHOUN','Fernand FAGNIHOUN','+22962067778'),
(1173,'FIDOCK LKN😊','FIDOCK LKN😊','+22962801619'),
(1174,'Fine ❣️','Fine ❣️','+22961920681'),
(1175,'Fine 🙂 ','Fine 🙂','+22956536542'),
(1176,'FJ ','FJ','98778888'),
(1177,'Flora ','Flora','+22996557175'),
(1178,'Francis 😇','Francis 😇','+212690226730'),
(1179,'Franck💖 ','Franck','+447360273551'),
(1180,'Franck💖 ','Franck','67626866'),
(1181,'Franck Lewis ','Franck Lewis','+22952025742'),
(1182,'Franklin ','Franklin','+22961020214'),
(1183,'Franklin ','Franklin','+22960361695'),
(1184,'Franklin ✨💜','Franklin ✨💜','+22962033768'),
(1185,'Frère Martin','Frère Martin','66507098'),
(1186,'Frères G','Frères G','53037450'),
(1187,'Frérot 🥰🔥✨😌🙃','Frérot 🥰🔥✨😌🙃','+22962048647'),
(1188,'Fritzell🥰 ','Fritzell🥰','+22991929541'),
(1189,'Gabin ✨🌟','Gabin BADJOGOUNME ✨🌟','+22990774240'),
(1190,'Gaston 😎','Gaston 😎','+971524570185'),
(1191,'Génis ','Génis','91656701'),
(1192,'Georges 😊🤗','Georges 😊🤗','97002816'),
(1193,'Gérald🥳 ','Gérald🥳','+22998244888'),
(1194,'Gerocrypto ','Gerocrypto','+22967417570'),
(1195,'Gerodeme 🌺🌸🌹','Gerodeme 🌺🌸🌹','+22962425759'),
(1196,'Ghislain 🚶🏾‍♀️','Ghislain 🚶🏾‍♀️','+22962900402'),
(1197,'Gilbert SETON','Gilbert SETON','+22996778056'),
(1198,'Gloria ','Gloria','91472335'),
(1199,'Godonou Prudrencia😇','Godonou Ve Prudrencia😇','+22966199798'),
(1200,'Gorilla Unit','Gorilla Unit','+22952727791'),
(1201,'Grâce 😍','Grâce 😍','67951438'),
(1202,'Halid Excellence','Halid Benin Excellence','+22967311229'),
(1203,'hmarthino🥷🏿 ','hmarthino🥷🏿','+22954074466'),
(1204,'Hôtel De Tour','Hôtel De La Tour','+33783112243'),
(1205,'Hôtel La House','Hôtel La Promenade House','+33769775261'),
(1206,'Hôtel Promenade ','Hôtel Promenade','+33493717213'),
(1207,'Hôtel Star ','Hôtel Star','+33689193333'),
(1208,'Hum 😁','Hum 😁','60050503'),
(1209,'Ibis Hôtel ','Ibis Hôtel','+33892683248'),
(1210,'Igor ⚡','Igor ⚡','+22990860090'),
(1211,'Ihab 🫠','Ihab 🫠','65294727'),
(1212,'Ilham ','Ilham','+22997672935'),
(1213,'Inc ','Inc','96660160'),
(1214,'Irving ✨🔥','Irving ✨🔥','+22961950171'),
(1215,'Jaspers ❤️🦋','Jaspers ❤️🦋','+22955742435'),
(1216,'Jean 🚶🏾‍♀️','Jean 🚶🏾‍♀️','96422097'),
(1217,'Jenny Agossou','Jenny Agossou','+22946011692'),
(1218,'Jéred😉 ','Jéred😉','+22966455162'),
(1219,'Joinitta ','Joinitta','+22961048884'),
(1220,'Jojo ','Jojo','+22962272868'),
(1221,'Jonas ','Jonas','+22996360783'),
(1222,'José ⚡','José ⚡','+22991266816'),
(1223,'José💜✨ ','José💜✨','+22961315193'),
(1224,'Jospin ','Jospin','+33650258031'),
(1225,'Josvy ✨⚡','Josvy Mendel ✨⚡','+242066937138'),
(1226,'Jouvence Hss❤️🍫🔥','Jouvence Hss❤️🍫🔥','+22965050412'),
(1227,'Judas FATONDE','Judas FATONDE','+22967386967'),
(1228,'Judi 😊','Judi 😊','+5571993069563'),
(1229,'Jules Dakou','Jules winriwan Dakou','+22940911381'),
(1230,'Junior ','Junior','+22966409470'),
(1231,'Junior 🤝🙂','Junior N 🤝🙂','58424242'),
(1232,'Justine 😌','Justine 😌','+22960654336'),
(1233,'Kader ⚡','Kader ⚡','+22654616809'),
(1234,'Kanno👑 ','Kanno👑','+22966291738'),
(1235,'Kéneth 😌 ','Kéneth 😌','+22962390139'),
(1236,'Kenneth ','Kenneth','41227986'),
(1237,'Kenneth 💜 ','Kenneth 💜','+22966120565'),
(1238,'Kévin ','Kévin','96484867'),
(1239,'Kévin Ponce DOVONOU','Kévin Ponce A. DOVONOU','+22997607393'),
(1240,'Kévin 💛','Kévin 💛','+22967771137'),
(1241,'Keynes 👀🚶🏾‍♀️💜','Keynes 👀🚶🏾‍♀️💜','+22961687707'),
(1242,'Kimba ','Kimba','+22967911346'),
(1243,'Kisito☺️ ','Kisito☺️','69550108'),
(1244,'Kissmath 😍😘🥰','Kissmath 😍😘🥰','+22953333464'),
(1245,'Kizito ✨❤️','Kizito ✨❤️','+22966183279'),
(1246,'Lambert ','Lambert','+22962967142'),
(1247,'Lari ','Lari','+22962940966'),
(1248,'Ledys ❤️✨','Ledys ❤️✨','+22967287356'),
(1249,'Léo ✨😎','Léo E-com ✨😎','+22951041464'),
(1250,'Léo UBA','Léo UBA','22961191924'),
(1251,'Lewis 🫠','Lewis 🫠','+22940911381'),
(1252,'Libéraure ❤️','Libéraure ❤️','62274353'),
(1253,'Lionel ⚡','Lionel ⚡','+22961678751'),
(1254,'Ludovic ','Ludovic','+22990466538'),
(1255,'LUNI SERVICE','LUNI SERVICE','+22951470912'),
(1256,'Maël Gbegnon','Maël Gbegnon','+22996532445'),
(1257,'Maman Chamak','Maman Chamak','98261381'),
(1258,'Maman 😍😍','Maman Hillary 😍😍','+22964085236'),
(1259,'Maman ❤️🥰','Maman Houéfa ❤️🥰','+22960201283'),
(1260,'Maman Keline','Maman Keline','69104595'),
(1261,'Maman Richenelle❤️😇 ','Maman Richenelle❤️😇','45414354'),
(1262,'Manassé 🥰♥️ ','Manassé 🥰♥️','43418509'),
(1263,'Manou🥰 ','Manou🥰','+22999356539'),
(1264,'Mario Btc','Mario Btc','+22961818225'),
(1265,'Marius Chef','Marius Chef','54263105'),
(1266,'Mathurin ','Mathurin','+22967425437'),
(1267,'Maucera Store','Maucera Store','+22999250572'),
(1268,'Max K 🥰✨ ','Max K 🥰✨','+22966557907'),
(1269,'Maxence ','Maxence','90175783'),
(1270,'Méca 1 ','Méca 1','+33675999329'),
(1271,'Méca 2','Méca 2','+33651388160'),
(1272,'Méca 3 ','Méca 3','+33148581128'),
(1273,'Méca 4 ','Méca 4','+33327739729'),
(1274,'Méca 5 ','Méca 5','+33144076225'),
(1275,'Mila ','Mila','+22995593160'),
(1276,'Milie🥰 ','Milie🥰','+22998101588'),
(1277,'Mimi 🥰 ','Mimi 🥰','54859147'),
(1278,'Mimou❤️ ','Mimou❤️','44779412'),
(1279,'Mohamed SANNI','Mohamed SANNI','+22967756575'),
(1280,'Mohamed 💜✨','Mohamed 💜✨','+22962949888'),
(1281,'Moussaid ','Moussaid','90760183'),
(1282,'Moustaïne ','Moustaïne','+22996616523'),
(1283,'Mr ','Mr','96805511'),
(1284,'Mr 😇😊','Mr Abdel 😇😊','+22997416306'),
(1285,'Mr Angelo','Mr Adjaï Angelo','95068011'),
(1286,'Mr Saibou','Mr Aziz Saibou','97770636'),
(1287,'Mr Brice','Mr Brice','+22996481646'),
(1288,'Mr Claude','Mr Claude','+22996975802'),
(1289,'Mr DE','Mr DE','97608565'),
(1290,'Mr Gildas','Mr Gildas','+22996229227'),
(1291,'Mr Ola','Mr Ola 🍯🦋','67471182'),
(1292,'Mr Zoul','Mr Zoul','+22996348601'),
(1293,'Mum ❤️♥️','Mum ❤️','96145562'),
(1294,'Mummy 🥰 ','Mummy 🥰','99364577'),
(1295,'Muri😍🦋🥰❤️ ','Muri😍🦋🥰❤️','99502858'),
(1296,'Narcisse A🥴🎉','Narcisse A🥴🎉','+22990207509'),
(1297,'Narcisse 🙄','Narcisse 🙄','+22960387462'),
(1298,'Nathalie ❤️🥰✨','Nathalie ❤️🥰✨','+22962228811'),
(1299,'Nel 🔥✨','Nel Tatoo 🔥✨','+22999378254'),
(1300,'Nella⚡ ','Nella⚡','+22961447445'),
(1301,'Nice Savoie Hôtel ','Nice Savoie Hôtel','+33493831869'),
(1302,'Nico ','Nico','+22990154979'),
(1303,'Nicolas Z','Nicolas Z','+22997370432'),
(1304,'Nisso🥰❤️ ','Nisso🥰❤️','+22966252242'),
(1305,'Ni~se 🧸','Ni~se 🧸','+22952414517'),
(1306,'Nova Chou','Nova Chou','+22968951506'),
(1307,'Océane ','Océane','51451963'),
(1308,'Od ','Od','+2250798275121'),
(1309,'Odile ☺️','Odile ☺️','91920898'),
(1310,'Olympas ','Olympas','+22996840925'),
(1311,'Ong ','Ong','62178400'),
(1312,'Ornéla🥰✨☺️ ','Ornéla🥰✨☺️','+22994279146'),
(1313,'Orou Djabarou','Orou Yari Djabarou','+22999124312'),
(1314,'Othinel 🤝🤗','Othinel 🤝🤗','59254411'),
(1315,'Oulfath 😘🤗🥰','Oulfath 😘🤗🥰','+22952117064'),
(1316,'PADONOU Ariel','PADONOU Ariel','+22960161950'),
(1317,'pamphile ','pamphile','+22994509127'),
(1318,'Papa ','Papa','66343750'),
(1319,'Parc 1','Parc 1','+22892929285'),
(1320,'Parc 2','Parc 2','+22890969164'),
(1321,'Patrick ✨⚡','Patrick ✨⚡','+22996170632'),
(1322,'Paul ','Paul','+22959033160'),
(1323,'Pedicure ','Pedicure','97470513'),
(1324,'Penielle ❤️','Penielle ❤️','90323306'),
(1325,'Petrick 🔥','Petrick 🔥','90088419'),
(1326,'Pierre ','Pierre','+22995256858'),
(1327,'Pierre Oussou','Pierre Oussou','+22966409956'),
(1328,'Play Zone','Play Zone','+22997851620'),
(1329,'Popo🥰❤️ ','Popo🥰❤️','52250984'),
(1330,'Princesse Latifatou','Princesse Latifatou','+22951748717'),
(1331,'Princio ❤️🎸🎹','Princio ❤️🎸🎹','+22999128438'),
(1332,'Prospect 01 ⚡ ','Prospect 01 ⚡','+212604452714'),
(1333,'Prudent😁 ','Prudent😁','+22961441819'),
(1334,'Quenum ','Quenum','+22962339170'),
(1335,'Raïssa ','Raïssa','57947067'),
(1336,'Ramanou BAMIGBOTCHE','Ramanou BAMIGBOTCHE','+22997144715'),
(1337,'Rayane✨🌚 ','Rayane✨🌚','+22969493073'),
(1338,'Raymond ','Raymond','+22966281244'),
(1339,'Raymond🥰❤️ ','Raymond🥰❤️','+447845082362'),
(1340,'Régis ','Régis','65193755'),
(1341,'Régis ','Régis','+22991575790'),
(1342,'Reine 💋 ','Reine 💋','+22994748286'),
(1343,'Reine 💝','Reine 💝','97311751'),
(1344,'Reine 😊','Reine 😊','+22669470449'),
(1345,'Renaud ❤️✨😎','Renaud ❤️✨😎','+22966316285'),
(1346,'Respo ','Respo','96131690'),
(1347,'Respo ','Respo','90647242'),
(1348,'ReventePro ','ReventePro','+22955493651'),
(1349,'Rezy Classinn','Rezy Classinn','+22991577248'),
(1350,'Rhetis ','Rhetis','+22946091959'),
(1351,'Rhode ⚡😌','Rhode ⚡😌','+22998684738'),
(1352,'Riad ✨💜','Riad ✨💜','+22967917551'),
(1353,'Riviera 💜🔥✨','Riviera 💜🔥✨','97933168'),
(1354,'Roddy E-com🔥✨😌','Roddy E-com🔥✨😌','66986620'),
(1355,'Rodolphe ','Rodolphe','96137301'),
(1356,'Rodrigue ','Rodrigue','66687462'),
(1357,'Rodrigue 🙏','Rodrigue 🙏','+22996456339'),
(1358,'Roland ','Roland','63136257'),
(1359,'Røm ','Røm','+22996369533'),
(1360,'Romek ','Romek','66941366'),
(1361,'Romel ','Romel','66461177'),
(1362,'Romuald H','Romuald H','67339161'),
(1363,'Rosias 😩🌚','Rosias 😩🌚','+22962557484'),
(1364,'Ruff 🥰❤️🥳','Ruff 🥰❤️🥳','+22969460191'),
(1365,'Sadik ❤️😇 ','Sadik ❤️😇','64420007'),
(1366,'Sagesse 💖😍✨','Sagesse 💖😍✨','+22952123890'),
(1367,'Saïd ','Saïd','+22990568704'),
(1368,'Saleck 👀','Saleck 👀','90326661'),
(1369,'Sam ','Sam','+22967612885'),
(1370,'Sarah ','Sarah','+22967652227'),
(1371,'Score ⚡','Score Exactes ⚡','+237699102386'),
(1372,'Serge 🌚🚶🏾‍♀️','Serge 🌚🚶🏾‍♀️','+22996865331'),
(1373,'Sethe💜 ','Sethe💜','62903424'),
(1374,'Sharma ','Sharma','+918171376730'),
(1375,'Shop ','Shop','62552649'),
(1376,'Sidney 😌💖🥰','Sidney 😌💖🥰','+22998108247'),
(1377,'Sidney 😌💖🥰','Sidney 😌💖🥰','+22952111461'),
(1378,'sikirourokibe ','sikirourokibe','+22996742406'),
(1379,'Simon Jude','Simon Jude','+22962428818'),
(1380,'Smoky ','Smoky','+2250172362557'),
(1381,'Soins 1','Soins 1','+22509637308'),
(1382,'Soins 1','Soins 1','+22509637308'),
(1383,'Soins 2','Soins 2','+2250708086451'),
(1384,'Soins 3','Soins 3','+2250759648089'),
(1385,'Soins 4 ','Soins 4','+237654589700'),
(1386,'Soins 5','Soins 5','+237651635148'),
(1387,'Soins 6 ','Soins 6','+33188321988'),
(1388,'Sostème Togla💰','Sostème Togla💰','+22967834773'),
(1389,'Soulemane ','Soulemane','+22994204758'),
(1390,'Stark🥳🥳 ','Stark🥳🥳','+22969054908'),
(1391,'Stefan ✨🎃😎','Stefan ✨🎃😎','+22962981812'),
(1392,'Steffii 😀','Steffii 😀','+22962131509'),
(1393,'Steven ⚡','Steven ⚡','+22896167233'),
(1394,'Superviseur Celtis','Superviseur Celtis','40050211'),
(1395,'Svenson Béni🦋','Svenson Béni🦋','+22960134336'),
(1396,'Sylvio 😊','Sylvio 😊','62261142'),
(1397,'S🔥 ','S🔥','+22962141970'),
(1398,'Technicien ','Technicien','61172651'),
(1399,'Technicien ','Technicien','97640758'),
(1400,'Technicienv ','Technicienv','+22997640758'),
(1401,'Théodore ','Théodore','+22951480018'),
(1402,'Thibauth ','Thibauth','+22962054610'),
(1403,'Tireur ','Tireur','91476930'),
(1404,'Tranquillin Montcho🔩','Tranquillin Montcho🔩','+22959000646'),
(1405,'UoPeople ','UoPeople','+16262648880'),
(1406,'Uriel Néry','Uriel Néry','+22964133232'),
(1407,'Victor 🌚🦋','Victor 🌚🦋','57238240'),
(1408,'Vioutou ','Vioutou','54948890'),
(1409,'Vital ','Vital','66403075'),
(1410,'Vp🥰 Raymond❤️','Vp🥰 Raymond❤️','+22962635228'),
(1411,'Wakli ','Wakli','61163999'),
(1412,'Wébo Olivier','Wébo Olivier','+22965683098'),
(1413,'Winock ','Winock','+22962669266'),
(1414,'Winock ','Winock','+22997090180'),
(1415,'Yann ','Yann','+22967429449'),
(1416,'Yanno ','Yanno','+22954572982'),
(1417,'Yessouf 💜✨','Yessouf 💜✨','+22991923026'),
(1418,'Zidane ❤️✨','Zidane ❤️✨','+22967571372'),
(1419,'1xbet ','1xbet','+2250544498375'),
(1420,'❤️✌🏽Angel 😇❤️','❤️✌🏽Angel Smile 😇❤️','+22996938297'),
(1421,'𝐄𝐜𝐨𝐦-𝐑𝐞́𝐮𝐬𝐬𝐢𝐭𝐞 ','𝐄𝐜𝐨𝐦-𝐑𝐞́𝐮𝐬𝐬𝐢𝐭𝐞','+22898076888'),
(1422,'💝 ','💝','90927613'),
(1423,'🙄 ','🙄','51858189'),
(1424,'🤏😎Davland❤‍🔥 ','🤏😎Davland❤‍🔥','+22952247183'),
(1425,'🤲🏽 Jack','🤲🏽 Jack','+22963017097');
/*!40000 ALTER TABLE `contacts_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deleted_ds`
--

DROP TABLE IF EXISTS `deleted_ds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deleted_ds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `motif` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `tel` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deleted_ds`
--

LOCK TABLES `deleted_ds` WRITE;
/*!40000 ALTER TABLE `deleted_ds` DISABLE KEYS */;
/*!40000 ALTER TABLE `deleted_ds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dsbonus`
--

DROP TABLE IF EXISTS `dsbonus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dsbonus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `montant` double NOT NULL,
  `date_exp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_36994080A76ED395` (`user_id`),
  CONSTRAINT `FK_36994080A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dsbonus`
--

LOCK TABLES `dsbonus` WRITE;
/*!40000 ALTER TABLE `dsbonus` DISABLE KEYS */;
/*!40000 ALTER TABLE `dsbonus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dsbonus_historique`
--

DROP TABLE IF EXISTS `dsbonus_historique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dsbonus_historique` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `dsbonus_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `montant` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_423BB934A76ED395` (`user_id`),
  KEY `IDX_423BB934FEDEBA63` (`dsbonus_id`),
  CONSTRAINT `FK_423BB934A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_423BB934FEDEBA63` FOREIGN KEY (`dsbonus_id`) REFERENCES `dsbonus` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dsbonus_historique`
--

LOCK TABLES `dsbonus_historique` WRITE;
/*!40000 ALTER TABLE `dsbonus_historique` DISABLE KEYS */;
INSERT INTO `dsbonus_historique` VALUES
(1,2,NULL,'2024-05-18 12:14:18','Parrainer par +22962273861',2000),
(2,5,NULL,'2024-05-18 12:14:18','+1 filleul +22958519556',2000),
(3,6,NULL,'2024-05-18 12:25:07','Parrainer par +22963856891',2000),
(4,8,NULL,'2024-05-18 12:25:07','+1 filleul +22969165323',2000);
/*!40000 ALTER TABLE `dsbonus_historique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `env`
--

DROP TABLE IF EXISTS `env`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `env` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commission_bonus` int(11) DEFAULT NULL,
  `version_app` varchar(255) NOT NULL,
  `important_update` tinyint(1) DEFAULT NULL,
  `users_tel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`users_tel`)),
  `do_boost_payant` tinyint(1) DEFAULT NULL,
  `link_local_server` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `env`
--

LOCK TABLES `env` WRITE;
/*!40000 ALTER TABLE `env` DISABLE KEYS */;
INSERT INTO `env` VALUES
(1,2000,'1.0.0',0,'[\"+22966369071\",\"+22960800573\",\"+22990927613\"]',1,'PAS_ENCORE_DE_LINK');
/*!40000 ALTER TABLE `env` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formule_boost`
--

DROP TABLE IF EXISTS `formule_boost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formule_boost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `prix` double NOT NULL,
  `nbr_jour` int(11) NOT NULL,
  `alert` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formule_boost`
--

LOCK TABLES `formule_boost` WRITE;
/*!40000 ALTER TABLE `formule_boost` DISABLE KEYS */;
INSERT INTO `formule_boost` VALUES
(1,'Formule A',100,2,0),
(2,'Formule B',1000,4,0),
(3,'Formule C',1500,7,0),
(4,'Formule D',3000,14,0),
(5,'Formule E',7000,30,0),
(6,'Formule F',12500,60,0),
(7,'Formule G',25000,120,0);
/*!40000 ALTER TABLE `formule_boost` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formule_campagne_mail`
--

DROP TABLE IF EXISTS `formule_campagne_mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formule_campagne_mail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `prix` int(11) NOT NULL,
  `nombre_mail` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formule_campagne_mail`
--

LOCK TABLES `formule_campagne_mail` WRITE;
/*!40000 ALTER TABLE `formule_campagne_mail` DISABLE KEYS */;
INSERT INTO `formule_campagne_mail` VALUES
(1,'Formule A',100,15),
(2,'Formule B',6000,100),
(3,'Formule C',9000,150),
(4,'Formule D',30000,500),
(5,'Formule E',55000,1000),
(6,'Formule F',330000,5000),
(7,'Formule G',600000,10000);
/*!40000 ALTER TABLE `formule_campagne_mail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formule_promo_reseau`
--

DROP TABLE IF EXISTS `formule_promo_reseau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formule_promo_reseau` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `titre` longtext NOT NULL,
  `icon_flutter_name` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `description_en` longtext DEFAULT NULL,
  `prix` double DEFAULT NULL,
  `qte` int(11) DEFAULT NULL,
  `qte_min` int(11) DEFAULT NULL,
  `qte_max` int(11) DEFAULT NULL,
  `available` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AC385F2727ACA70` (`parent_id`),
  CONSTRAINT `FK_AC385F2727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `formule_promo_reseau` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formule_promo_reseau`
--

LOCK TABLES `formule_promo_reseau` WRITE;
/*!40000 ALTER TABLE `formule_promo_reseau` DISABLE KEYS */;
INSERT INTO `formule_promo_reseau` VALUES
(1,NULL,'TikTok','tiktok',NULL,NULL,NULL,NULL,NULL,NULL,1),
(2,1,'Followers',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0-5%\n🔗 Lien du Profil\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0-5%\n🔗 Profile Link\n🔓 Public profile required',6.82,1000,100,50000,1),
(3,1,'Vues',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien de la Vidéo\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Video Link\n🔓 Public profile required',0.01,1000,100,10000000,1),
(4,1,'Likes Rapide',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien de la Vidéo\n🔓 Profil public obligatoire','🔝 Quality: Average\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Video Link\n🔓 Public profile required',1.19,1000,10,30000,1),
(5,1,'Likes Lent',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Lent\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien de la Vidéo\n🔓 Profil public obligatoire','🔝 Quality: Average\n⚡ Speed: Slow\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Video Link\n🔓 Public profile required',0.67,1000,10,50000,1),
(6,1,'Save',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Recup : Non\n🔗 Lien de la Vidéo\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Recovery: No\n🔗 Video Link\n🔓 Public profile required',0.45,1000,10,20000,1),
(7,NULL,'Instagram','instagram',NULL,NULL,NULL,NULL,NULL,NULL,1),
(8,7,'Followers',NULL,'🔝 Qualité : Très bonne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0-5%\n🔗 Lien du profil\n🔓 Profil public obligatoire','🔝 Quality: Very good\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0-5%\n🔗 Profile link\n🔓 Public profile required',1.3,1000,20,500000,1),
(9,7,'Likes Rapide',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie : Non\n📉 Taux de perte : 0-20%\n🔗 Lien de la photo/vidéo\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Warranty: No\n📉 Loss rate: 0-20%\n🔗 Photo/video link\n🔓 Public profile required',0.28,1000,10,50000,1),
(10,7,'Likes Lent',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Lent\n♻️ Garantie : Non\n📉 Taux de perte : 0-20%\n🔗 Lien de la photo/vidéo\n🔓 Profil public obligatoire','🔝 Quality: Average\n⚡ Speed: Slow\n♻️ Warranty: No\n📉 Loss rate: 0-20%\n🔗 Photo/video link\n🔓 Public profile required',0.13,1000,10,100000,1),
(11,7,'Likes Bot',NULL,'🔝 Qualité : Mauvaise\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0-1%\n🔗 Lien de la photo/vidéo\n🔓 Profil public obligatoire','🔝 Quality: Bad\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0-1%\n🔗 Photo/video link\n🔓 Public profile required',0.08,1000,10,100000,1),
(12,7,'Comments Emoji Positive',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien de la photo/vidéo\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Photo/video link\n🔓 Public profile required',1.8,1000,10,200000,1),
(13,7,'Comment Like',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Lent\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien : Voir la note\n🔓 Profil public obligatoire\n\n\n⚠️ Note : Pour récupérer le bon lien : trouvez votre commentaire dans le navigateur (sur un ordinateur), lorsque vous cliquez sur les secondes, les minutes où les heures à gauche du commentaire, vous verrez que le lien a changé. Vous pouvez alors copier ce lien et indiquer la quantité. Lorsque seul le nom d\'utilisateur est saisi, votre commentaire ne peut être retrouvé parmi des milliers d\'autres et cela nécessite de nombreuses requêtes. Par conséquent, le lien doit être créé de cette manière.','🔝 Quality: Good\n⚡ Speed: Slow\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Link: See note\n🔓 Public profile required\n\n\n⚠️ Note: To retrieve the correct link: find your comment in the browser (on a computer), when you click on the seconds, minutes or hours to the left of the comment, you will see that the link has changed. You can then copy this link and indicate the quantity. When only the username is entered, your comment cannot be found among thousands of others and this requires many queries. Therefore, the link must be created in this way.',3.28,1000,5,5000,1),
(14,7,'Story Views',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie : 24 heures\n📉 Taux de perte : 0%\n🔗 Lien du profil\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Guarantee: 24 hours\n📉 Loss rate: 0%\n🔗 Profile link\n🔓 Public profile required',0.42,1000,10,15000,1),
(15,7,'Vues de Reels Lent',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Lent\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien du Reel\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Slow\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Reel link\n🔓 Public profile required',0.1,1000,100,50000000,1),
(16,7,'Vues de Reels Rapide',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien du Reel\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Reel link\n🔓 Public profile required',0.11,1000,150,10000000,1),
(17,7,'Channel Member',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0-5%\n🔗 Lien du canal\n🔓 Canal public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0-5%\n🔗 Channel link\n🔓 Mandatory public channel',9.66,1000,10,100000,1),
(18,NULL,'Twitter/X','twitter',NULL,NULL,NULL,NULL,NULL,NULL,1),
(19,18,'Followers',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Rapide\n♻️ Garantie : Non\n📉 Taux de perte : 80-100%\n🔗 Lien du Profil\n🔓 Profil public obligatoire','🔝 Quality: Average\n⚡ Speed: Fast\n♻️ Warranty: No\n📉 Loss rate: 80-100%\n🔗 Profile Link\n🔓 Public profile required',1.43,1000,10,40000,1),
(20,18,'Likes',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Rapide\n♻️ Garantie : Non\n📉 Taux de perte : 80-100%\n🔗 Lien du Tweet\n🔓 Profil public obligatoire','🔝 Quality: Average\n⚡ Speed: Fast\n♻️ Warranty: No\n📉 Loss rate: 80-100%\n🔗 Tweet link\n🔓 Public profile required',1.2,1000,10,25000,1),
(21,18,'Retweets',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Rapide\n♻️ Garantie : Non\n📉 Taux de perte : 80-100%\n🔗 Lien du Tweet\n🔓 Profil public obligatoire','🔝 Quality: Average\n⚡ Speed: Fast\n♻️ Warranty: No\n📉 Loss rate: 80-100%\n🔗 Tweet link\n🔓 Public profile required',0.82,1000,50,25000,1),
(22,18,'Retweets Views',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien du Tweet\n🔓 Profil public obligatoire','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Tweet link\n🔓 Public profile required',0.01,1000,100,100000000,1),
(23,NULL,'YouTube','youtube',NULL,NULL,NULL,NULL,NULL,NULL,1),
(24,23,'Followers',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Lent\n♻️ Garantie à vie\n📉 Taux de perte : 0-5%\n🔗 Lien de la Chaine\n\n\n⚠️ Note : Pour assurer le bon fonctionnement de notre service, une vidéo d’au moins une minute doit être publiée sur votre chaîne YouTube pendant la période de traitement de votre commande. En l\'absence de vidéo répondant à ce critère, la commande sera automatiquement annulée. Veuillez vous assurer de respecter cette exigence pour bénéficier de notre service.','🔝 Quality: Good\n⚡ Speed: Slow\n♻️ Lifetime warranty\n📉 Loss rate: 0-5%\n🔗 Chain Link\n\n\n⚠️ Note: To ensure the proper functioning of our service, a video of at least one minute must be published on your YouTube channel during the processing period of your order. In the absence of a video meeting this criterion, the order will be automatically canceled. Please ensure you meet this requirement to benefit from our service.',20.23,1000,100,200000,1),
(25,23,'Vues',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Lent\n♻️ Garantie à vie\n📉 Taux de perte : 0-5%\n🔗 Lien de la vidéo','🔝 Quality: Good\n⚡ Speed: Slow\n♻️ Lifetime warranty\n📉 Loss rate: 0-5%\n🔗 Video link',4.17,1000,100,30000,1),
(26,23,'Likes',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0-5%\n🔗 Lien de la vidéo','🔝 Quality: Average\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0-5%\n🔗 Video link',2.76,1000,10,30000,1),
(27,NULL,'Facebook','Facebook',NULL,NULL,NULL,NULL,NULL,NULL,1),
(28,27,'Followers',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Lent\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien de la page\n🔓 Page publique obligatoire','🔝 Quality: Average\n⚡ Speed: Slow\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Page link\n🔓 Mandatory public page',3.98,1000,100,50000,1),
(29,27,'Page Likes',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Rapide\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien de la page\n🔓 Profil public obligatoire','🔝 Quality: Average\n⚡ Speed: Fast\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Page link\n🔓 Public profile required',3.68,1000,100,30000,1),
(30,NULL,'Telegram','Telegram',NULL,NULL,NULL,NULL,NULL,NULL,1),
(31,30,'Members',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Lent\n♻️ Garantie : Non\n📉 Taux de perte : 0-10%\n🔗 Lien du canal\n🔓 Canal public obligatoire','🔝 Quality: Average\n⚡ Speed: Slow\n♻️ Warranty: No\n📉 Loss rate: 0-10%\n🔗 Channel link\n🔓 Mandatory public channel',1.1,1000,10,50000,1),
(32,30,'Post Views',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Lent\n♻️ Garantie à vie\n📉 Taux de perte : 0%\n🔗 Lien du post\n🔓 Canal public obligatoire','🔝 Quality: Good\n⚡ Speed: Slow\n♻️ Lifetime warranty\n📉 Loss rate: 0%\n🔗 Post link\n🔓 Mandatory public channel',0.04,1000,100,500000,1),
(33,NULL,'Twitch','Twitch',NULL,NULL,NULL,NULL,NULL,NULL,1),
(34,33,'Followers',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Lent\n♻️ Garantie : Non\n📉 Taux de perte : 0-5%\n🔗 Lien du Profil','🔝 Quality: Average\n⚡ Speed: Slow\n♻️ Warranty: No\n📉 Loss rate: 0-5%\n🔗 Profile Link',3.71,1000,10,10000,1),
(35,NULL,'Spotify','Spotify',NULL,NULL,NULL,NULL,NULL,NULL,1),
(36,35,'Followers',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Rapide\n♻️ Garantie : Non\n📉 Taux de perte : 80-100%\n🔗 Lien: https://open.spotify.com/artist/.... OU https://open.spotify.com/user/....','🔝 Quality: Good\n⚡ Speed: Fast\n♻️ Warranty: No\n📉 Loss rate: 80-100%\n🔗 Link: https://open.spotify.com/artist/.... OR https://open.spotify.com/user/....',0.64,1000,20,100000000,1),
(37,35,'Auditeurs Mentuels',NULL,'🔝 Qualité : Bonne\n⚡ Vitesse : Lent\n♻️ Garantie : Non\n📉 Taux de perte : 80-100%\n🔗 Lien: https://open.spotify.com/artist/....','🔝 Quality: Good\n⚡ Speed: Slow\n♻️ Warranty: No\n📉 Loss rate: 80-100%\n🔗 Link: https://open.spotify.com/artist/....',7.3,1000,1000,50000,1),
(38,35,'Plays',NULL,'🔝 Qualité : Moyenne\n⚡ Vitesse : Lent\n♻️ Garantie : Non\n📉 Taux de perte : 80-100%\n🔗 Lien du track','🔝 Quality: Average\n⚡ Speed: Slow\n♻️ Warranty: No\n📉 Loss rate: 80-100%\n🔗 Track link',1.86,1000,500,10000000,1);
/*!40000 ALTER TABLE `formule_promo_reseau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messenger_messages`
--

LOCK TABLES `messenger_messages` WRITE;
/*!40000 ALTER TABLE `messenger_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messenger_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mot_refuser`
--

DROP TABLE IF EXISTS `mot_refuser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mot_refuser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mot` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mot_refuser`
--

LOCK TABLES `mot_refuser` WRITE;
/*!40000 ALTER TABLE `mot_refuser` DISABLE KEYS */;
/*!40000 ALTER TABLE `mot_refuser` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preference`
--

DROP TABLE IF EXISTS `preference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preference` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pays_choisies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`pays_choisies`)),
  `centre_interet_loisir_choisies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`centre_interet_loisir_choisies`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_5D69B053A76ED395` (`user_id`),
  CONSTRAINT `FK_5D69B053A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preference`
--

LOCK TABLES `preference` WRITE;
/*!40000 ALTER TABLE `preference` DISABLE KEYS */;
INSERT INTO `preference` VALUES
(1,1,'[]','[]'),
(2,2,'[\"229\",\"226\",\"224\",\"225\",\"223\",\"227\",\"228\"]','[]'),
(3,3,'[]','[]'),
(4,4,'[]','[]'),
(5,5,'[]','[]'),
(6,6,'[\"355\",\"213\",\"376\",\"244\",\"1264\",\"1268\",\"54\",\"374\",\"297\",\"247\",\"61\",\"43\",\"994\",\"1242\",\"229\",\"262\"]','[\"2\",\"3\",\"4\",\"5\",\"6\",\"8\"]'),
(7,7,'[]','[]'),
(8,8,'[]','[]'),
(9,9,'[]','[]'),
(10,10,'[\"229\"]','[]'),
(11,11,'[\"229\"]','[]'),
(12,12,'[\"229\"]','[]');
/*!40000 ALTER TABLE `preference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_reseau`
--

DROP TABLE IF EXISTS `promo_reseau`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_reseau` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `formule_promo_reseau_id` int(11) NOT NULL,
  `qte_demander` int(11) NOT NULL,
  `prix_fixer` int(11) NOT NULL,
  `url` longtext NOT NULL,
  `status` int(11) NOT NULL,
  `id_zefame` varchar(255) DEFAULT NULL,
  `compteur_debut` int(11) DEFAULT NULL,
  `compteur_restant` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D46033F5A76ED395` (`user_id`),
  KEY `IDX_D46033F594989D80` (`formule_promo_reseau_id`),
  CONSTRAINT `FK_D46033F594989D80` FOREIGN KEY (`formule_promo_reseau_id`) REFERENCES `formule_promo_reseau` (`id`),
  CONSTRAINT `FK_D46033F5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_reseau`
--

LOCK TABLES `promo_reseau` WRITE;
/*!40000 ALTER TABLE `promo_reseau` DISABLE KEYS */;
INSERT INTO `promo_reseau` VALUES
(1,2,1,8400,101,'https://vm.tiktok.com/ZMM3VYb4u/',1,'*****',0,0,'2024-05-18 11:55:18','2024-05-18 11:55:18'),
(2,7,1,10000,120,'https://vm.tiktok.com/ZGeXMSXH7/',1,'*****',0,0,'2024-05-18 18:07:21','2024-05-18 18:07:21');
/*!40000 ALTER TABLE `promo_reseau` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion`
--

DROP TABLE IF EXISTS `promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formule_boost_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `description` longtext NOT NULL,
  `image` longtext NOT NULL,
  `date_debut` datetime DEFAULT NULL,
  `date_exp` datetime DEFAULT NULL,
  `status` int(11) NOT NULL,
  `nombre_de_vue` int(11) NOT NULL,
  `mode` varchar(255) NOT NULL,
  `nombre_impression` int(11) DEFAULT NULL,
  `limited` tinyint(1) NOT NULL,
  `who_saw` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`who_saw`)),
  PRIMARY KEY (`id`),
  KEY `IDX_C11D7DD17BBCC0BB` (`formule_boost_id`),
  KEY `IDX_C11D7DD1A76ED395` (`user_id`),
  CONSTRAINT `FK_C11D7DD17BBCC0BB` FOREIGN KEY (`formule_boost_id`) REFERENCES `formule_boost` (`id`),
  CONSTRAINT `FK_C11D7DD1A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion`
--

LOCK TABLES `promotion` WRITE;
/*!40000 ALTER TABLE `promotion` DISABLE KEYS */;
INSERT INTO `promotion` VALUES
(1,1,2,'🚀 Découvrez BLUE LIFE TECH - Votre Porte d\'Accès à l\'Informatique et Plus Encore! 🚀\nVous cherchez des solutions informatiques innovantes? Vous êtes passionné par la technologie et le numérique? Ou peut-être êtes-vous à la recherche d\'une opportunité pour développer vos compétences dans le domaine de l\'informatique? Ne cherchez plus, BLUE LIFE TECH est là pour répondre à tous vos besoins!\n💼 Nos Services 💼\nConception de Sites Web 🌐\nDéveloppement d\'Applications Mobiles 📱\nMaintenance Informatique 💻\nGénie Logiciel 🧠\nRéseaux Informatiques & Sécurité 🔒\nGraphisme & Communication 🎨\nÉlectricité & Énergie ⚡\n👨‍🎓 Formations Professionnelles 👩‍🎓\nNous offrons également une gamme complète de formations professionnelles dans tous nos domaines d\'activité. Que vous soyez débutant ou professionnel aguerri, nos programmes de formation vous aideront à acquérir des compétences précieuses pour exceller dans le monde de la technologie.\n👥 Stages & Opportunités 👥\nBLUE LIFE TECH propose des opportunités passionnantes de stage dans tous nos domaines d\'activité. Rejoignez notre équipe et travaillez sur des projets innovants qui stimuleront votre créativité et renforceront vos compétences.\n🌐 Nous Contacter 🌐\nEmail : bluelife.tech@gmail.com 📧\nWhatsApp : +229 58 51 95 56 📱\nSuivez nous sur Instagram, TikTok, YouTube et Facebook pour les dernières mises à jour !\n📢 Partagez cette publication! 📢\nVous pourriez aider quelqu\'un à trouver la carrière de ses rêves ou la solution informatique parfaite. Partagez ce flyer et laissez nous vous accompagner vers un avenir numérique brillant!\n💼 Pourquoi choisir BLUE LIFE TECH ? 💼\nBLUE LIFE TECH est votre partenaire de confiance pour tout ce qui concerne l\'informatique. Nous combinons l\'expertise, l\'innovation et la passion pour vous offrir des solutions sur mesure qui répondent à vos besoins.\n🚀 Rejoignez la révolution numérique avec BLUE LIFE TECH. 🚀\nL\'avenir commence ici! 💻🌟','promotion__2c09b04b80e6af225cf84b833cd94a38.png','2024-05-18 11:52:19','2024-05-20 11:52:19',3,73,'Payant',170,1,'[2,6,7,10,11,12,8]'),
(2,1,6,'Au passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Au passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Au passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesMoi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Au passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesMoi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Au passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.Moi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesMoi c\'est ALLADAYE Dynams \nJe me suis présenté et je te demande rien a part de m\'enregistrer, c\'est juste pour avoir des vuesAu passage, chez moi commande : \n\nSite Web\nAffiche, Logo, etc...\n\nChez moi tu peut aussi écrire a tous tes contacts, pratique pour que ceux qui ont perdu ton numéro t\'enregistre et du côté professionnel pour faire de la publicité personnalisé.','promotion__d2d17143b7b6158cadc54a1b6806cc3a.jpg','2024-05-18 12:28:52','2024-05-20 12:28:52',3,79,'Gratuit',159,1,'[2,6,7,10,11,12,8]'),
(3,NULL,7,'Profitez d\'un accès à vie à Canva Pro pour seulement 8,99$','promotion__45c982bcde64b7348103fb0431321ace.jpg',NULL,NULL,2,0,'Gratuit',0,1,'[]');
/*!40000 ALTER TABLE `promotion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signalement`
--

DROP TABLE IF EXISTS `signalement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signalement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `signaler_id` int(11) NOT NULL,
  `signalant_id` int(11) NOT NULL,
  `motif` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F4B5511487C5F038` (`signaler_id`),
  KEY `IDX_F4B551146CA2C067` (`signalant_id`),
  CONSTRAINT `FK_F4B551146CA2C067` FOREIGN KEY (`signalant_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_F4B5511487C5F038` FOREIGN KEY (`signaler_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signalement`
--

LOCK TABLES `signalement` WRITE;
/*!40000 ALTER TABLE `signalement` DISABLE KEYS */;
/*!40000 ALTER TABLE `signalement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction`
--

DROP TABLE IF EXISTS `transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `id_transaction` varchar(255) DEFAULT NULL,
  `reference` varchar(255) NOT NULL,
  `amount` int(11) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `currency_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `annother_info` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `transaction_for` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_723705D1A76ED395` (`user_id`),
  CONSTRAINT `FK_723705D1A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction`
--

LOCK TABLES `transaction` WRITE;
/*!40000 ALTER TABLE `transaction` DISABLE KEYS */;
INSERT INTO `transaction` VALUES
(1,2,'97842318','trx_3-k_1716032754345',100,'approved',2721179,1,'2024-05-18 11:45:55','2024-05-18 11:46:21','a:1:{s:13:\"formulBoostId\";i:1;}','boost_contact'),
(2,2,'97842497','trx_Gms_1716033106954',100,'approved',2721179,1,'2024-05-18 11:51:48','2024-05-18 11:52:19','a:2:{s:13:\"formulBoostId\";i:1;s:11:\"promotionId\";i:1;}','boost_affaire'),
(3,2,'97842597','trx_qb0_1716033296547',101,'approved',2721179,1,'2024-05-18 11:54:57','2024-05-18 11:55:18','a:5:{s:20:\"idFormulePromoReseau\";s:1:\"1\";s:11:\"qteDemander\";s:4:\"8400\";s:15:\"prixQteDemander\";s:3:\"101\";s:4:\"lien\";s:32:\"https://vm.tiktok.com/ZMM3VYb4u/\";s:3:\"tel\";s:12:\"+22962273861\";}','boost_reseau_sociaux'),
(4,2,'97843177','trx_B9E_1716034387744',100,'approved',2721179,1,'2024-05-18 12:13:09','2024-05-18 12:13:31','a:1:{s:14:\"idCampagneMail\";s:1:\"1\";}','campagne_mail'),
(5,6,'97843880','trx_Law_1716035601649',7448,'canceled',2721422,1,'2024-05-18 12:33:22','2024-05-18 12:33:32','a:5:{s:20:\"idFormulePromoReseau\";s:1:\"1\";s:11:\"qteDemander\";s:4:\"1000\";s:15:\"prixQteDemander\";s:4:\"7448\";s:4:\"lien\";s:51:\"https://www.tiktok.com/@dynams7?_t=8mS7uquCES1&_r=1\";s:3:\"tel\";s:12:\"+22969165323\";}','boost_reseau_sociaux'),
(6,6,'97843910','trx_C8o_1716035645302',745,'canceled',2721422,1,'2024-05-18 12:34:06','2024-05-18 12:39:13','a:5:{s:20:\"idFormulePromoReseau\";s:1:\"1\";s:11:\"qteDemander\";s:2:\"10\";s:15:\"prixQteDemander\";s:3:\"745\";s:4:\"lien\";s:51:\"https://www.tiktok.com/@dynams7?_t=8mS7uquCES1&_r=1\";s:3:\"tel\";s:12:\"+22969165323\";}','boost_reseau_sociaux'),
(7,7,'97853004','trx_frY_1716055619270',120,'approved',2627659,1,'2024-05-18 18:07:00','2024-05-18 18:07:21','a:5:{s:20:\"idFormulePromoReseau\";s:1:\"1\";s:11:\"qteDemander\";s:5:\"10000\";s:15:\"prixQteDemander\";s:3:\"120\";s:4:\"lien\";s:32:\"https://vm.tiktok.com/ZGeXMSXH7/\";s:3:\"tel\";s:12:\"+22997542851\";}','boost_reseau_sociaux'),
(8,7,'97853154','trx_q0A_1716056031423',100,'approved',2627659,1,'2024-05-18 18:13:52','2024-05-18 18:14:21','a:1:{s:13:\"formulBoostId\";i:1;}','boost_contact');
/*!40000 ALTER TABLE `transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parrain_id` int(11) DEFAULT NULL,
  `uid` longtext DEFAULT NULL,
  `pseudo` varchar(255) DEFAULT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `mail` varchar(255) DEFAULT NULL,
  `pays` int(11) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `apropos` longtext DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `mail_is_verified` tinyint(1) DEFAULT NULL,
  `tel_is_verified` tinyint(1) DEFAULT NULL,
  `solde_bonus` double DEFAULT NULL,
  `code_bonus` varchar(255) DEFAULT NULL,
  `theme_dark` tinyint(1) DEFAULT NULL,
  `admin` tinyint(1) DEFAULT NULL,
  `blocked` tinyint(1) NOT NULL,
  `tiktok` longtext DEFAULT NULL,
  `instagram` longtext DEFAULT NULL,
  `facebook` longtext DEFAULT NULL,
  `youtube` longtext DEFAULT NULL,
  `lang` varchar(2) NOT NULL,
  `last_login_to` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8D93D649DE2A7A37` (`parrain_id`),
  CONSTRAINT `FK_8D93D649DE2A7A37` FOREIGN KEY (`parrain_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES
(1,NULL,'664894672ae0c','profil.google',NULL,'equipe.test.dressur.ds@gmail.com',229,'+22900000000',NULL,'71356a4a4a8958811fdda1b9eb2964aac1c67bdb','2024-05-18 11:43:35',1,1,2000,'DS0000',0,0,0,NULL,NULL,NULL,NULL,'en',NULL),
(2,5,'664894672b7a1','bluelife','BLUE LIFE TECH : ENTREPRISE INFORMATIQUE','bluelife.tech@gmail.com',229,'+22958519556','','0b7d7285c0ba8776119c97efa88367dbec9cce26','2024-05-18 11:43:35',1,1,52000,'BLUE-LIFE-TECH',0,1,0,'https://www.tiktok.com/@bluelife.tech?_t=8mS7159YYWU&_r=1','https://www.instagram.com/bluelife.tech?igsh=Mjcyc2tpMmw4dXhu','https://www.facebook.com/bluelife.tech','https://youtube.com/@bluelife-tech?si=VO76F17Q32a5axgh','fr','2024-05-20 01:59:30'),
(3,NULL,'664894672b7be','elitics.core',NULL,'elitics.core@tech-center.com',229,'+22990978787',NULL,'0b7d7285c0ba8776119c97efa88367dbec9cce26','2024-05-18 11:43:35',1,1,50000,'ELITICS-CORE',0,0,0,NULL,NULL,NULL,NULL,'fr',NULL),
(4,NULL,'664894672b7d6','louise',NULL,'affichekpolouise@gmail.com',229,'+22956518088',NULL,'0b7d7285c0ba8776119c97efa88367dbec9cce26','2024-05-18 11:43:35',1,1,50000,'LOUISE',0,0,0,NULL,NULL,NULL,NULL,'fr',NULL),
(5,NULL,'664894672b7ef','dklars',NULL,'dklars.dev@gmail.com',229,'+22962273861',NULL,'0b7d7285c0ba8776119c97efa88367dbec9cce26','2024-05-18 11:43:35',1,1,52000,'DKLARS',0,0,0,NULL,NULL,NULL,NULL,'fr',NULL),
(6,8,'664894672b808','san',NULL,'alladayesandym@gmail.com',229,'+22969165323',NULL,'efd64872071da72080869724432eb9b7dc4d31bf','2024-05-18 11:43:35',1,1,26900,'SAN',0,0,0,NULL,NULL,NULL,NULL,'fr','2024-05-18 12:46:37'),
(7,NULL,'664894672b81f','noe','GOUTON Samson Noé','noegouton@gmail.com',229,'+22997542851','CEO Etilis Core','c6f8b5fad05fb8585fa03860fabb073fba33561a','2024-05-18 11:43:35',1,1,50000,'NOE',0,1,0,'https://www.tiktok.com/@eliticscore1','https://instagram.com/eliticscore','https://facebook.com/eliticscore','','fr','2024-05-20 11:32:25'),
(8,NULL,'664894672b835','dev',NULL,'kofukunoatama@gmail.com',229,'+22963856891',NULL,'0b7d7285c0ba8776119c97efa88367dbec9cce26','2024-05-18 11:43:35',1,1,52000,'DEV',0,0,0,NULL,NULL,NULL,NULL,'fr','2024-05-19 18:43:17'),
(9,NULL,'664894672b84c','dynams',NULL,'dynamslars@gmail.com',229,'+22955487985',NULL,'0b7d7285c0ba8776119c97efa88367dbec9cce26','2024-05-18 11:43:35',1,1,50000,'DYNAMS',0,0,0,NULL,NULL,NULL,NULL,'fr',NULL),
(10,NULL,'6648f59b4ca01','michele',NULL,'michelekoty2@gmail.com',229,'+22966369071',NULL,'4b5dcbe0cebaba2fc2b460aea64de0452eb36160','2024-05-18 18:38:19',0,1,2000,'DSDTPZO',0,0,0,NULL,NULL,NULL,NULL,'fr','2024-05-18 18:45:24'),
(11,NULL,'6648fae68e650','andy',NULL,'andersonfachina@outlook.fr',229,'+22960800573',NULL,'297eedf88052d5d0bb50a8c9d747473eafdd5cb8','2024-05-18 19:00:54',0,1,2000,'DSMUBE4',0,0,0,NULL,NULL,NULL,NULL,'en','2024-05-18 19:26:16'),
(12,NULL,'6649ee031563c','jeunesse',NULL,'jouvencehouefa910@gmail.com',229,'+22990927613',NULL,'ef359240927c68f7db4f41fa2c557e1abb36e4f9','2024-05-19 12:18:11',1,1,2000,'DSO93E7',0,0,0,NULL,NULL,NULL,NULL,'fr','2024-05-19 12:22:18');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verif_mail`
--

DROP TABLE IF EXISTS `verif_mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verif_mail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `date_exp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EAFE0190A76ED395` (`user_id`),
  CONSTRAINT `FK_EAFE0190A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verif_mail`
--

LOCK TABLES `verif_mail` WRITE;
/*!40000 ALTER TABLE `verif_mail` DISABLE KEYS */;
INSERT INTO `verif_mail` VALUES
(1,10,'DSQCZEQB','2024-05-18 18:43:19'),
(2,11,'DSBPMGXK','2024-05-18 19:05:54');
/*!40000 ALTER TABLE `verif_mail` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-05-20 21:03:27
