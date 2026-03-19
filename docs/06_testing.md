# 06 — Testing y Ambientes

## 1. Estado Actual

### 1.1. Tests Automatizados
- **No existen** tests automatizados (unit tests, integration tests, E2E).
- No hay framework de testing configurado (PHPUnit, Codeception, etc.).
- No hay cobertura de código.

### 1.2. Testing Manual
- El testing se realiza directamente en **producción**.
- No hay ambiente de staging ni de desarrollo local configurado.
- Cada mejora se sube directamente al servidor de producción para verificar.

### 1.3. Riesgos Actuales
| Riesgo                                     | Impacto | Probabilidad |
|--------------------------------------------|---------|--------------|
| Bug introducido en producción sin detectar | Alto    | Alta         |
| SPs con errores desplegados sin validar    | Alto    | Media        |
| Integración ERP rota por cambio de código  | Alto    | Media        |
| Regresión visual en otras pantallas        | Medio   | Alta         |
| Datos de producción corrompidos por prueba | Alto    | Baja         |

---

## 2. Estrategia Propuesta de Ambientes

### 2.1. Opción A: Desarrollo Local + Producción (recomendada)

```
PC Local (XAMPP/Laragon)        Servidor (cPanel)
┌─────────────────────┐        ┌───────────────────────┐
│  PHP 8.4            │        │  webapp.puduhue.cl    │
│  MariaDB 10.11.x    │   ──►  │  api.puduhue.cl       │
│  Apache             │  FTP/  │                       │
│  .env (local)       │  Git   │  .env (producción)    │
└─────────────────────┘        └───────────────────────┘
   desarrollo + testing           producción
```

**Pros**: Cero costo adicional, testing seguro sin afectar producción.
**Contras**: Datos de prueba separados de los reales.

### 2.2. Opción B: Staging en Servidor + Producción

```
Servidor cPanel
┌──────────────────────────────┐
│  staging.puduhue.cl          │  ← subdominio adicional
│  .env (staging con BD copia) │
├──────────────────────────────┤
│  webapp.puduhue.cl           │
│  .env (producción)           │
└──────────────────────────────┘
```

**Pros**: Mismo entorno que producción, datos más realistas.
**Contras**: Requiere subdominio adicional, copia de BD, consumo de recursos compartidos.

### 2.3. Opción C: Ambas (ideal)
- Desarrollo local para iteración rápida.
- Staging en servidor para validación final pre-deploy.
- Producción solo recibe código probado.

---

## 3. Herramientas Recomendadas para Ambiente Local

### 3.1. Servidor Local

| Herramienta | OS      | PHP 8.4 | MariaDB | Facilidad |
|-------------|---------|---------|---------|-----------|
| XAMPP       | Win/Mac | ✅      | ✅      | Alta      |
| Laragon     | Windows | ✅      | ✅      | Alta      |
| Docker      | Todos   | ✅      | ✅      | Media     |
| WAMP        | Windows | ✅      | ✅      | Alta      |

> [!TIP]
> **Recomendación**: Laragon para Windows. Es portable, configura automáticamente Apache + PHP + MariaDB, y soporta múltiples versiones de PHP.

### 3.2. Setup Local Mínimo
1. Instalar Laragon (o XAMPP) con PHP 8.4 + MariaDB 10.11.x.
2. Crear BD local e importar scripts SQL (`database/tables/*.sql`, `database/sp/*.sql`).
3. Ejecutar scripts de inicialización (`init_empresa.sql`, `init_root.sql`, etc.).
4. Copiar `.env` y ajustar credenciales locales.
5. Configurar virtual host apuntando a `apps/web-php/`.
6. Probar login con usuario ROOT.

---

## 4. Testing Mínimo Recomendado

### 4.1. Fase 1: Smoke Tests Manuales (inmediato)

Checklist básico para cada deploy:

- [ ] Login funciona (usuario/contraseña correctos).
- [ ] Login rechaza credenciales incorrectas.
- [ ] Dashboard carga sin errores.
- [ ] Al menos 1 listado de maestro carga datos.
- [ ] Al menos 1 operación CRUD completa (crear → listar → editar → anular).
- [ ] Integración Finnegans: 1 envío de producción de leche (en ambiente de prueba ERP si existe).
- [ ] Exportar a Excel funciona.
- [ ] API externa: 1 query con Bearer token válido.

### 4.2. Fase 2: Test de Regresión Visual
- Capturar screenshots de pantallas clave antes de cambios UX.
- Comparar después de aplicar cambios.
- Herramientas opcionales: BackstopJS, Percy, o simplemente capturas manuales.

### 4.3. Fase 3: Tests Automatizados (futuro)
- Configurar PHPUnit.
- Tests unitarios para Services (lógica de negocio).
- Tests de integración para SPs (con BD de prueba).
- Tests E2E para flujos críticos (Selenium/Playwright, solo si se justifica).

---

## 5. Flujo de Deploy Propuesto

### 5.1. Git Flow Simplificado
```
main (producción)
  └── develop (desarrollo activo)
        └── feature/xxx (ramas por feature)
```

### 5.2. Proceso de Deploy

1. **Desarrollo**: trabajar en rama `feature/xxx` en ambiente local.
2. **Test local**: probar en Laragon/XAMPP.
3. **Merge a develop**: revisión y merge.
4. **Deploy a staging** (si existe): subir a `staging.puduhue.cl`.
5. **Test en staging**: smoke tests.
6. **Deploy a producción**: subir a `webapp.puduhue.cl` / `api.puduhue.cl`.
7. **Verificación post-deploy**: checklist mínimo.

### 5.3. Deploy Técnico (cPanel sin SSH)
- Subir archivos vía **File Manager de cPanel** o **FTP/SFTP**.
- Scripts SQL → ejecutar en **phpMyAdmin**.
- Alternativa: configurar **Git Deployment** en cPanel (disponible en algunos planes).

---

## 6. Base de Datos en Desarrollo

### 6.1. Datos de Prueba
- Usar datos mínimos: 1 empresa, 1 fundo, 1 usuario ROOT, menús base.
- Scripts de inicialización ya existen en `database/init_*.sql`.
- Para transacciones: crear registros de prueba manualmente o con script seed.

### 6.2. Sincronización de Esquema
- BD de desarrollo y producción deben tener el mismo esquema.
- Usar scripts de `database/alter_table/` para cambios incrementales.
- Mantener el `CHANGELOG.md` actualizado con cada cambio de esquema.
