BEGIN;

INSERT INTO users (password, pseudo, email, lang) VALUES
    ('0b9c2625dc21ef05f6ad4ddf47c5f203837aa32c', -- sha1 of "toto"
    'arno', 'arno@renevier.net', 'fr');

INSERT INTO users (password, pseudo, email, lang) VALUES
    ('0b9c2625dc21ef05f6ad4ddf47c5f203837aa32c', -- sha1 of "toto"
    'user', 'user@renevier.net', 'en');

INSERT INTO paths (geom, creator, title, creator_ip) VALUES
    (ST_GeographyFromText ('SRID=4326;LINESTRING(2.437892 48.638297, 2.431111 48.643628, 2.436218 48.648108, 2.415104 48.660355)'),
    1, 'ris-orangis', '127.0.0.1');

INSERT INTO paths (geom, creator, title, creator_ip) VALUES
    (ST_GeographyFromText ('SRID=4326;LINESTRING(2.466474 48.649242, 2.459993 48.651766, 2.478619 48.656160, 2.462826 48.672883, 2.468061 48.690564)'),
    1, 'Forêt de Sénart', '127.0.0.1');

INSERT INTO paths (geom, creator, title, urlcomp, creator_ip) VALUES
    (ST_GeographyFromText ('SRID=4326; LINESTRING(2.452204 48.634880, 2.453599 48.635745, 2.449908 48.637744, 2.448149 48.638595, 2.442999 48.642593, 2.440360 48.645500, 2.438471 48.646847, 2.436712 48.647938, 2.433729 48.650036, 2.423387 48.655877, 2.415297 48.660384, 2.417142 48.661659, 2.417700 48.662297, 2.417700 48.662921, 2.416434 48.663941, 2.415361 48.665018, 2.411671 48.671211, 2.410340 48.672585, 2.408237 48.674272, 2.407122 48.675717, 2.405341 48.677403, 2.403431 48.678933, 2.402251 48.680619, 2.401006 48.680605, 2.399504 48.680662, 2.397702 48.684189, 2.397294 48.684203, 2.395213 48.683849, 2.391522 48.684359, 2.392466 48.688057, 2.389891 48.688467, 2.386994 48.689402, 2.386029 48.689742, 2.386351 48.690280, 2.387445 48.690040, 2.389269 48.692589, 2.396221 48.699034, 2.399182 48.702206, 2.400384 48.703934, 2.401328 48.705718, 2.403173 48.710589, 2.404118 48.712288, 2.406478 48.714440, 2.408667 48.715459, 2.412915 48.718234, 2.417250 48.720527, 2.423215 48.722962, 2.428107 48.724123, 2.433043 48.724463, 2.440381 48.726246, 2.442688 48.727386, 2.441475 48.727754, 2.440048 48.727563, 2.441733 48.728518, 2.443042 48.728256, 2.445359 48.727860, 2.445037 48.727265, 2.444565 48.727449, 2.445724 48.729558, 2.445853 48.732163, 2.445917 48.735630, 2.444522 48.738460, 2.444673 48.738574, 2.443085 48.740229, 2.440810 48.741842, 2.437935 48.744984, 2.435575 48.748493, 2.428579 48.754774, 2.421241 48.760546, 2.414289 48.766628, 2.413559 48.769117, 2.413731 48.771521, 2.415104 48.773473, 2.415919 48.775481, 2.415962 48.776640, 2.422957 48.787160, 2.424116 48.789619, 2.423773 48.792418, 2.421498 48.796857, 2.420340 48.798270, 2.423601 48.798949, 2.428966 48.800164, 2.427936 48.800673, 2.427163 48.801521, 2.427721 48.802426, 2.428579 48.802765, 2.425833 48.806977, 2.422442 48.812064, 2.420254 48.815342, 2.419739 48.817037, 2.419953 48.824186, 2.422957 48.824130, 2.423429 48.824610, 2.426906 48.828622, 2.429094 48.831984, 2.430382 48.834696, 2.431326 48.835176, 2.432270 48.839921, 2.433043 48.841136, 2.434416 48.844921, 2.415361 48.846700, 2.414589 48.846982, 2.413816 48.846898, 2.396865 48.848423, 2.396007 48.848959, 2.395191 48.848592, 2.384119 48.850258, 2.380342 48.850286, 2.374763 48.851642, 2.369356 48.853223, 2.365150 48.853873, 2.360902 48.855172, 2.347941 48.858588, 2.340817 48.860960, 2.335238 48.862682, 2.335539 48.863247, 2.332363 48.870587, 2.323651 48.869203, 2.321806 48.866747, 2.321334 48.866296, 2.320261 48.864771, 2.313738 48.864912, 2.313437 48.862767, 2.310390 48.862597, 2.309961 48.857148, 2.303181 48.852800)'),
        2, 'Convergence 2010 départ d''Évry', 'convergence_2010', '127.0.0.1');

INSERT INTO paths (geom, title, creator_ip) VALUES
    (ST_GeographyFromText ('SRID=4326; LINESTRING(6.1404762560273 45.86358137584,6.1422787004853 45.863790569888,6.1436949068451 45.863730800241,6.1436949068451 45.862595164723,6.142965345993 45.861519278098,6.1428365999603 45.860413484038,6.142965345993 45.859397329565,6.1441669756317 45.857843175039,6.1451111132049 45.857424741398,6.1458835894012 45.856737307867,6.1469135576628 45.85365869694,6.1492739015959 45.851147858942,6.1509905153653 45.848517335649,6.1526642137905 45.845647531989,6.1542091661831 45.844421841557,6.156011610641 45.843495082082,6.1586294466394 45.845498058991,6.1596164995569 45.844750687982,6.1630497270957 45.844870268018,6.1642513567344 45.844690897867,6.1652813249961 45.844182679299,6.1657533937827 45.843614664817,6.167427092208 45.843823933985,6.1681995684042 45.843584769157,6.166182547225 45.840923991102,6.1675558382407 45.840505430162,6.168714552535 45.839309524412,6.169444113387 45.839219830445,6.1702595049275 45.839429116143,6.171246557845 45.839309524412,6.1747656160724 45.84352497779)'),
        'trajet le 31/07/2010', '127.0.0.1');

INSERT INTO paths (geom, title, creator_ip) VALUES
    (ST_GeographyFromText ('SRID=4326; LINESTRING(6.0852879564305 45.75070325035,6.1009949724211 45.752260396733,6.1002224962248 45.755164957535,6.0973042528168 45.755973416636,6.0970467607513 45.757500474093,6.0940856019989 45.758308899357,6.0930127183931 45.760225119867,6.0912102739352 45.760314941088,6.0907382051485 45.762261032051,6.0879487077732 45.764296869945,6.0852450410863 45.766182947578,6.0804814378759 45.765434511704,6.0813826601049 45.759057430826,6.0852879564305 45.75070325035)'),
        'allèves 20100804', '127.0.0.1');

COMMIT;
