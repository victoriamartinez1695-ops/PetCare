# Guía de estudio: desarrollo de PetCare

Este documento explica el proceso, las decisiones técnicas y las herramientas usadas para construir PetCare. Es material de estudio; agregarlo no cambia las páginas ni la ejecución de la aplicación.

## 1. ¿Qué problema resuelve?

PetCare ayuda a un veterinario o cuidador a organizar la información de sus usuarios, las mascotas, sus tareas de cuidado y los reportes de salud. En cada reporte se puede registrar el problema observado, una solución o tratamiento y su estado.

El proyecto se desarrolló con PHP nativo, MySQL de XAMPP, PDO, HTML y CSS. No utiliza un framework PHP ni un gestor de dependencias como Composer.

## 2. Cómo se fue construyendo

### Paso 1: definir las entidades principales

Se partió de tres tablas:

- `users`: cuentas de usuario (`id`, `name`, `email`, `password`).
- `pets`: mascotas con su propietario (`user_id`) y sus datos.
- `tasks`: tareas con su propietario (`user_id`) y la mascota relacionada (`pet_id`).

La relación prevista fue:

```text
users 1 ─── N pets 1 ─── N tasks
  └──────────────────── N tasks
```

Un usuario puede tener varias mascotas y tareas. Cada tarea pertenece a una mascota y al usuario que la creó.

### Paso 2: preparar XAMPP y la base MySQL

Se usó XAMPP como servidor local:

1. Iniciar Apache y MySQL desde XAMPP Control Panel.
2. Abrir phpMyAdmin en `http://localhost/phpmyadmin`.
3. Importar el SQL de las tablas iniciales.
4. Confirmar que las tablas estén dentro de la base correcta.

Durante la configuración apareció una diferencia entre el nombre de base del esquema inicial (`petcare`) y el nombre de la base que ya se estaba usando (`veterinaria petca`). Se adaptó la conexión de PHP al nombre real. Una conexión selecciona una base por nombre: que las tablas existan en otra base no basta.

La configuración final se encuentra en `conexion\conexion.php`. Usa PDO con:

- Host `localhost`.
- Puerto `3306`.
- Base `veterinaria petca`.
- Usuario local predeterminado `root`.
- Contraseña local predeterminada vacía.
- Juego de caracteres `utf8mb4`.

La contraseña vacía es una configuración común de XAMPP local, no una recomendación para un servidor público.

### Paso 3: enlazar PHP con MySQL usando PDO

El flujo de conexión es:

1. Una página PHP incluye `auth.php`.
2. `auth.php` incluye `conexion\conexion.php`.
3. `conexion.php` construye el DSN MySQL y crea el objeto `$pdo`.
4. Las páginas usan `$pdo` para consultar o modificar los datos.

Se configuró PDO para lanzar excepciones en caso de error, devolver resultados asociativos y desactivar las preparaciones emuladas. Las consultas con datos proporcionados por el usuario usan sentencias preparadas.

### Paso 4: registro, login y sesión

- `registro.php` valida nombre, correo y longitud de contraseña y guarda una cuenta en `users`.
- La contraseña se convierte en hash mediante `password_hash()` antes de guardarse.
- `index.php` busca la cuenta por correo y comprueba la contraseña con `password_verify()`.
- Al autenticar, se regenera el identificador de sesión y se guardan los datos necesarios en `$_SESSION`.
- `auth.php` ofrece `require_login()` para restringir páginas a usuarios autenticados.
- `logout.php` valida la solicitud, limpia la sesión y cierra el acceso.

Para evitar revelar si una cuenta concreta existe, el login usa un mensaje genérico cuando el correo o la contraseña no coinciden.

### Paso 5: gestión de mascotas

`mascotas.php` implementa operaciones CRUD:

- **Create:** insertar una mascota en `pets` con el `user_id` de la sesión.
- **Read:** cargar una mascota propia para editarla y mostrar sus datos en el panel.
- **Update:** guardar cambios solo si el `id` y el `user_id` corresponden al usuario activo.
- **Delete:** borrar solo registros del usuario activo.

La foto se puede seleccionar desde los archivos locales. El servidor admite JPG, PNG y WebP de hasta 5 MB, valida el tipo real, genera un nombre aleatorio y guarda el archivo dentro de `uploads\pets`. La tabla guarda la ruta relativa en `photo`. Al reemplazar o quitar una foto se limpia el archivo anterior cuando corresponde.

### Paso 6: gestión de tareas

`tareas.php` permite crear, consultar, editar y borrar tareas. Cada tarea contiene título, descripción, categoría, fecha, hora, prioridad y estado.

Antes de guardarla, el servidor comprueba que la mascota seleccionada pertenezca al usuario de la sesión. También verifica los formatos y valores permitidos. En la base, las claves foráneas relacionan las tareas con su usuario y su mascota.

### Paso 7: reportes de salud y soluciones

Después se agregó la función de reportes independientes mediante `health_problems`. Para conservar el diseño previo, se creó una migración adicional en vez de cambiar las tablas originales:

- `migracion_problemas_salud.sql` crea `health_problems`, relacionada con un usuario y una mascota.
- `problemas.php` permite crear, listar, editar y eliminar reportes.
- El campo `solution` documenta la solución o tratamiento indicado por el veterinario.
- `migracion_solucion_problemas.sql` agrega ese campo a una tabla de reportes ya instalada.
- Los estados se guardan como `pending`, `in_review` o `resolved`.

La pantalla limita las operaciones a los reportes del usuario autenticado y a mascotas de su cuenta.

### Paso 8: panel, interfaz y paginación

`dashboard.php` muestra las mascotas, las tareas y el acceso al seguimiento de salud. Se añadieron:

- Tarjetas de mascotas con fotos pequeñas que se pueden ampliar.
- Un bloque separado de seguimiento de salud.
- Paginación de seis mascotas por página; las tareas y otros accesos permanecen en el panel.
- Mensajes de confirmación después de guardar o borrar.

`style.css` da un estilo de temática veterinaria con colores naturales, tarjetas, encabezados y formularios responsive. Las páginas agregan la fecha de modificación de la hoja de estilos a su URL para evitar que el navegador conserve una versión antigua en caché.

### Paso 9: pruebas y depuración

Durante el trabajo se verificaron errores de rutas, de conexión y de tablas. También se usó la comprobación de sintaxis de PHP de XAMPP (`php -l`) y se probaron páginas y flujos en el navegador: login, registro, panel, carga de foto, CRUD de reportes y navegación de la paginación.

Las pruebas manuales con datos temporales se limpiaron al terminar. Para una entrega formal todavía es recomendable agregar pruebas automatizadas y repetir la demostración en el equipo donde se presentará.

## 3. Cómo se integra la base de datos

### Tablas y relaciones actuales

```text
users
  ├── 1:N pets
  ├── 1:N tasks
  └── 1:N health_problems

pets
  ├── 1:N tasks
  └── 1:N health_problems
```

Las tareas y los reportes guardan `user_id` y `pet_id`. La clave foránea compuesta verifica que la mascota relacionada pertenezca al mismo usuario; no basta con enviar un `pet_id` válido de otra cuenta.

Las claves foráneas usan eliminación en cascada. Al borrar una mascota se eliminan sus tareas y reportes relacionados; al borrar un usuario se eliminan sus datos asociados.

### Qué significa “conectar” la aplicación a la base

La base no se conecta desde el CSS o directamente desde el navegador. La ruta es:

```text
Navegador → página PHP → PDO → MySQL/MariaDB de XAMPP
```

El navegador envía un formulario por HTTP. PHP valida la sesión y los datos, PDO ejecuta SQL en la base y PHP devuelve una página HTML con el resultado.

## 4. Archivos principales

| Archivo | Responsabilidad |
| --- | --- |
| `index.php` | Inicio de sesión |
| `registro.php` | Registro de cuentas |
| `auth.php` | Sesión, CSRF, validaciones comunes, escape HTML y utilidades de fotos |
| `conexion\conexion.php` | Conexión PDO |
| `dashboard.php` | Panel de mascotas paginado, tareas y acceso a reportes |
| `mascotas.php` | CRUD de mascotas y subida de fotos |
| `tareas.php` | CRUD de tareas |
| `problemas.php` | CRUD de reportes y soluciones de salud |
| `logout.php` | Cierre de sesión |
| `style.css` | Apariencia y adaptación a pantallas |
| `migracion_problemas_salud.sql` | Creación de la tabla de reportes |
| `migracion_solucion_problemas.sql` | Campo solución para reportes existentes |
| `README.md` | Instrucciones de uso y referencia rápida |

## 5. Buenas prácticas aplicadas

- Consultas PDO preparadas para valores que llegan de formularios.
- Contraseñas guardadas como hash, no como texto plano.
- Regeneración del identificador de sesión al iniciar sesión.
- Tokens CSRF en formularios que cambian información.
- Validación en el servidor; las validaciones HTML del navegador son solo una ayuda adicional.
- Comprobación de propiedad del usuario antes de modificar o borrar registros.
- Escape HTML de los valores mostrados para reducir riesgo de inyección de contenido.
- Claves foráneas para proteger la integridad de las relaciones.
- Validación del MIME real y tamaño de las fotos; nombres de archivos aleatorios.

Para una instalación pública habría que reforzar la configuración: usar un usuario MySQL con permisos limitados, una contraseña segura fuera del código, HTTPS, cookies de sesión seguras, respaldos y registros de errores que no expongan detalles internos al usuario.

## 6. Uso de VS Code con Copilot

Se usó VS Code como editor y asistencia de Copilot para:

- Proponer y completar código PHP para formularios y operaciones.
- Explicar errores reportados por PHP y ayudar a localizar causas.
- Ayudar a integrar PDO con MySQL y las relaciones del esquema.
- Iterar sobre la presentación y los estilos responsive.
- Preparar documentación e instrucciones de estudio.

La IA no sustituyó la revisión: fue necesario comparar las sugerencias con el nombre real de la base, la ubicación verdadera de los archivos y el comportamiento de la aplicación en XAMPP. Un ejemplo concreto fue corregir el nombre de la base usado en PDO después de encontrar una diferencia entre `petcare` y `veterinaria petca`.

Para explicar tu experiencia en primera persona, completa después de estudiar:

- ¿Qué parte te ayudó a terminar más rápido?
- ¿Qué sugerencia tuviste que corregir o comprobar?
- ¿Qué aprendiste al revisar un error en vez de copiar la solución sin probarla?

## 7. Ventajas y desventajas del IDE con IA

### Ventajas observadas en este proyecto

- Ayuda a empezar una función a partir de una descripción en lenguaje natural.
- Sugiere código repetitivo y ahorra escritura.
- Explica mensajes de error y permite seguir un proceso de depuración conversacional.
- Puede ayudar a mantener consistencia entre varias páginas y a redactar documentación.
- VS Code es ligero, extensible y funciona bien junto con XAMPP y el navegador.

### Desventajas y límites

- Puede asumir rutas, esquemas o versiones que no coinciden con el proyecto real.
- El código generado puede compilar y aun así tener errores lógicos; debe probarse.
- Una respuesta convincente no garantiza que sea correcta o segura.
- La calidad de la experiencia de PHP en VS Code depende de las extensiones y configuración instaladas.
- La IA no conoce por sí sola todos los datos de la instalación local; hay que proporcionarle contexto sin compartir secretos.
- El uso del asistente no demuestra que se hayan aprendido los conceptos: el estudiante debe poder explicar y defender el código.

### Comparación general con PhpStorm con AI Assistant

| Tema | VS Code con Copilot | PhpStorm con AI Assistant |
| --- | --- | --- |
| Enfoque | Editor general, configurable mediante extensiones. | IDE enfocado en desarrollo, con herramientas especializadas para PHP. |
| Ventaja | Flexible, ligero y con muchas extensiones. | Análisis, navegación y herramientas PHP integradas en una sola experiencia. |
| Límite | Algunas capacidades dependen de extensiones y configuración. | Puede requerir más recursos; disponibilidad y costo dependen de la licencia y funciones vigentes. |
| En este proyecto | Fue suficiente para editar PHP, CSS, SQL y probar en XAMPP. | Podría aportar herramientas PHP más profundas, pero no fue el IDE usado para construir este proyecto. |

La comparación es general, no una prueba directa de ambos productos. Para una exposición honesta, presenta lo que efectivamente usaste y no afirmes experiencia práctica con un IDE que no probaste.

## 8. GitHub, copias de seguridad y despliegue

- El proyecto se mantuvo localmente en `C:\xampp\htdocs\PetCa`.
- **No se ha publicado en GitHub**, porque se decidió esperar a terminar más cambios.
- XAMPP permite ejecutar la aplicación en `http://localhost/PetCa/`; esto es un entorno local, no un sitio publicado en Internet.
- Se preparó una copia local en `C:\Users\victo\Downloads\PetCare\respaldo_2026-10-11`, con el código y una exportación SQL. Esa copia se creó antes de los últimos cambios posteriores, por lo que no debe confundirse con una copia actualizada de la versión de hoy.
- Para proteger el trabajo actual, hacer una nueva copia tanto de `C:\xampp\htdocs\PetCa` como de la base de datos exportada desde phpMyAdmin. Una copia de código por sí sola no respalda los datos de MySQL.

El trabajo profesional con GitHub queda pendiente hasta que se cree un repositorio, se revise que no contenga credenciales ni datos personales y se decida publicarlo.

## 9. Guion para estudiar y demostrar el proyecto

1. Explica el objetivo: centralizar mascotas, tareas y seguimiento veterinario.
2. Muestra en phpMyAdmin `users`, `pets`, `tasks` y `health_problems`.
3. Explica las relaciones y por qué cada registro incluye el dueño.
4. Inicia XAMPP y muestra cómo se abre PetCare desde `localhost`.
5. Registra o inicia sesión con una cuenta de demostración.
6. Agrega o edita una mascota y selecciona una foto local.
7. Muestra las tarjetas, amplía una foto y recorre páginas si existen más de seis mascotas.
8. Crea una tarea para una mascota y muestra cómo cambiar su estado.
9. Registra un problema, escribe la solución indicada y explica el estado del reporte.
10. Muestra que las operaciones usan la cuenta conectada y cierra sesión.
11. Explica qué hizo Copilot, qué verificaste y una limitación que encontraste.
12. Aclara que el proyecto sigue local, sin publicación en GitHub ni despliegue público.

## 10. Glosario rápido

- **PDO:** interfaz de PHP para conectarse a bases de datos.
- **DSN:** cadena que indica a PDO el motor, host, puerto, base y juego de caracteres.
- **CRUD:** crear (Create), consultar (Read), actualizar (Update) y eliminar (Delete).
- **Hash de contraseña:** representación no reversible usada para verificar una contraseña sin guardarla en texto plano.
- **Clave foránea:** restricción que mantiene válida una relación entre tablas.
- **Migración:** cambio SQL incremental al esquema de una base ya existente.
- **CSRF:** tipo de solicitud no deseada que se intenta enviar en nombre de un usuario autenticado; un token ayuda a verificar que el formulario se originó en la aplicación.
- **Responsive:** diseño que adapta su distribución al tamaño de pantalla.
- **localhost:** dirección que apunta al equipo local; otras personas en Internet no pueden acceder automáticamente a ella.
