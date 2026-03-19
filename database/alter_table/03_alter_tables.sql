/*
Cambios realizados despues de la primera creacion de las tablas.
Modificacion del tipo de dato de las columnas "auditcreacionusuarioid" y "auditedicionusuarioid" de las tablas que contienen dichas columnas.
Se cambia de VARCHAR(12) a INT(11) para que guarden el ID del usuario en lugar del nombre de usuario.
Se elmina la restriccion NOT NULL de la columna "auditedicionusuarioid" para permitir valores nulos.
auditcreacionusuariocod eliminado de todas las tablas. 
auditcreacionusuarionom eliminado de todas las tablas.
*/

ALTER TABLE `clientes` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `empresas` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `estanquesmarcas` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `fundos` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `fundosestanques` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `fundosestanquesclientes` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `fundosestanquesclientes` ADD UNIQUE KEY `uq_fundosestanquesclientes_estanqueclientecod` (`estanqueclientecod`);
ALTER TABLE `invbodegas` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `invcateganimal` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `invitems` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `invunidadesmedidas` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `menus` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `perfiles` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `perfilesmenus` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `prodleche` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `prodlechetipos` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;
ALTER TABLE `retiroleche` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `retirolechedetalle` ADD COLUMN `estanqueclientecod` INT(11) NOT NULL AFTER `clienteid`;
ALTER TABLE `suplanimal` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `usuarios` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;	
ALTER TABLE `usuariosempresas` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL;	
ALTER TABLE `usuariosempresashist` MODIFY COLUMN histauditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;
ALTER TABLE `usuariosfundos` MODIFY COLUMN auditcreacionusuarioid INT(11) NOT NULL;
ALTER TABLE `usuariosfundoshist` MODIFY COLUMN histauditcreacionusuarioid INT(11) NOT NULL, MODIFY COLUMN auditedicionusuarioid INT(11) NULL;
