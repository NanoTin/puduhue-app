# Playbook: Setup de Ambiente Local de Desarrollo

## Objetivo
Configurar un ambiente de desarrollo local para poder probar cambios antes de subirlos a producción.

---

## 1. Instalar Servidor Local

### Opción Recomendada: Laragon (Windows)
1. Descargar Laragon Full desde: https://laragon.org/download/
2. Instalar con opciones por defecto.
3. Verificar versiones:
   - PHP ≥ 8.4 (actualizar si es necesario desde https://www.php.net/downloads).
   - MariaDB ≥ 10.11 (actualizar desde panel de Laragon si es necesario).
   - Apache 2.4+.
4. Iniciar Laragon ("Start All").

### Alternativa: XAMPP
1. Descargar desde: https://www.apachefriends.org/
2. Instalar con PHP 8.4, MariaDB, Apache.
3. Iniciar servicios desde el panel de XAMPP.

---

## 2. Configurar el Proyecto

### 2.1. Clonar/Copiar el Repositorio
```bash
cd C:\laragon\www
git clone <url-del-repo> puduhue
```
O copiar la carpeta del proyecto manualmente.

### 2.2. Configurar Virtual Host
En Laragon:
1. Abrir `Menu > Apache > sites-enabled > auto.puduhue.test.conf` (se crea automáticamente).
2. O crear manualmente:
```apache
<VirtualHost *:80>
    DocumentRoot "C:/laragon/www/puduhue/apps/web-php"
    ServerName webapp.puduhue.test
</VirtualHost>

<VirtualHost *:80>
    DocumentRoot "C:/laragon/www/puduhue/apps/api-php"
    ServerName api.puduhue.test
</VirtualHost>
```
3. Editar archivo hosts (`C:\Windows\System32\drivers\etc\hosts`):
```
127.0.0.1  webapp.puduhue.test
127.0.0.1  api.puduhue.test
```
4. Reiniciar Apache.

### 2.3. Configurar `.env`
1. Copiar `.env` de producción (o `.env.example` si existe).
2. Modificar:
```env
# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=puduhue_dev
DB_USER=root
DB_PASS=

# App
APP_ENV=development
APP_DEBUG=true
APP_URL=http://webapp.puduhue.test
```

---

## 3. Configurar Base de Datos Local

### 3.1. Crear Base de Datos
1. Abrir phpMyAdmin local (http://localhost/phpmyadmin) o HeidiSQL.
2. Crear base de datos: `puduhue_dev` (charset `utf8mb4_unicode_ci`).

### 3.2. Importar Estructura
Ejecutar en orden:

```
1. database/tables/01_table_*.sql       (todas las tablas)
2. database/alter_table/*.sql          (si existen)
3. database/sp/02_sp_*.sql             (todos los SPs)
4. database/sp/03_sp_login.sql         (SP de login)
```

### 3.3. Datos Iniciales
```
5. database/init_empresa.sql
6. database/init_root.sql
7. database/init_menus.sql
8. database/init_perfiles_menus.sql
```

### 3.4. Verificar
- Acceder a `http://webapp.puduhue.test`.
- Login con usuario ROOT.
- Navegar al dashboard.

---

## 4. Configurar Composer (opcional, solo local)

```bash
cd C:\laragon\www\puduhue
composer install
```

> [!NOTE]
> Composer se usa solo en local. En producción se sube `vendor/` completo. Si no se necesitan dependencias nuevas (ej. PhpSpreadsheet ya funciona), este paso es opcional.

---

## 5. Extensiones PHP Requeridas

Verificar que estén habilitadas en `php.ini`:
- `mysqli`
- `curl`
- `mbstring`
- `pdo_mysql`
- `openssl`

En Laragon: `Menu > PHP > Extensions`.

---

## 6. Estructura de Carpetas de Storage

Crear si no existen:
```
storage/LOGS/
storage/APILog/
storage/temp/
apps/web-php/uploads/retiroleche/img/
```

---

## 7. Workflow de Desarrollo

```
1. Abrir proyecto en VS Code / PhpStorm
2. Hacer cambios en rama feature/xxx
3. Probar en http://webapp.puduhue.test
4. Si OK → commit → push
5. Deploy a producción (ver playbook de deploy)
```

---

## 8. Troubleshooting

| Problema                              | Solución                                     |
|---------------------------------------|----------------------------------------------|
| "Class not found" en Controller       | Verificar `require_once` del archivo          |
| Página en blanco                      | Revisar `storage/LOGS/` y `php_error.log`    |
| SP no encontrado                      | Ejecutar el script SQL del SP en phpMyAdmin   |
| Login no funciona                     | Verificar `.env` con credenciales de BD local |
| reCAPTCHA falla en local              | El site key de reCAPTCHA puede no funcionar en localhost; evaluar desactivar temporalmente en `.env` |
