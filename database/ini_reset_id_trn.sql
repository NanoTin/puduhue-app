-- Eliminar los datos de prueba de producción de leche, partien por LOG, despues DETALLE y finalmente la cabecera
DELETE FROM prodlechelog;
DELETE FROM prodlechedetalle;
DELETE FROM prodleche;
-- Reiniciar los contadores de AUTO_INCREMENT para que comiencen desde 1
ALTER TABLE prodleche AUTO_INCREMENT = 1;

-- Eliminar datos de prueba de suplementación animal
DELETE FROM suplanimallog;
DELETE FROM suplanimaldetalle;
DELETE FROM suplanimal;
-- Reiniciar los contadores de AUTO_INCREMENT para que comiencen desde 1
ALTER TABLE suplanimal AUTO_INCREMENT = 1;

-- Eliminar datos de prueba de retiro de leche
DELETE FROM retirolechelog;
DELETE FROM retirolechedetalle;
DELETE FROM retiroleche;
-- Reiniciar los contadores de AUTO_INCREMENT para que comiencen desde 1
ALTER TABLE retiroleche AUTO_INCREMENT = 1;