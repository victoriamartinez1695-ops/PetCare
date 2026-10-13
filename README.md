# PetCare

Aplicación web para organizar mascotas y sus tareas de cuidado. Está desarrollada en PHP nativo, usa PDO para conectarse con MySQL de XAMPP y ofrece registro, inicio de sesión y operaciones CRUD para mascotas y tareas.

## Requisitos

- XAMPP con Apache y MySQL.
- PHP con las extensiones PDO y `pdo_mysql` habilitadas.
- Una base MySQL llamada exactamente `veterinaria petca`, con las tablas `users`, `pets` y `tasks`.

## Puesta en marcha

1. Copia el proyecto dentro de `C:\xampp\htdocs\PetCa`.
2. Inicia Apache y MySQL desde XAMPP Control Panel.
3. En phpMyAdmin, importa el archivo SQL de la base. Si la base ya existe, selecciona `veterinaria petca` y confirma que aparecen las tres tablas.
4. Importa `migracion_problemas_salud.sql` si todavía no tienes la tabla `health_problems`.
5. Importa `migracion_solucion_problemas.sql` para añadir el campo `solution` a la tabla de reportes existente. Hazlo una sola vez; conserva los reportes ya registrados.
6. Revisa `conexion\conexion.php`. La configuración local predeterminada es `localhost`, puerto `3306`, usuario `root` y contraseña vacía. Ajusta estos valores si tu instalación usa otras credenciales.
7. Abre `http://localhost/PetCa/`.
8. Selecciona **Crear cuenta**, registra un usuario y luego agrega una mascota desde el panel.
9. Agrega una tarea y asígnala a esa mascota. Usa **Problemas de salud** para registrar un reporte y documentar la solución o tratamiento indicado.

La contraseña de cada usuario se almacena como hash usando `password_hash()`; no se guarda en texto plano.
Al crear o editar una mascota puedes elegir una imagen JPG, PNG o WebP desde tu equipo. El servidor valida el contenido y el tamaño (máximo 5 MB) y guarda el archivo en `uploads\pets`; la base de datos almacena su ruta relativa. Al reemplazar o borrar una foto, se elimina el archivo anterior.
Si PHP rechaza archivos por tamaño antes de que la aplicación los valide, ajusta `upload_max_filesize` a `5M` o más y `post_max_size` a `6M` o más en `C:\xampp\php\php.ini`, reinicia Apache y vuelve a intentarlo.

## Funcionalidad y rutas

| Archivo | Función |
| --- | --- |
| `index.php` | Inicio de sesión con correo y contraseña |
| `registro.php` | Registro de usuario |
| `dashboard.php` | Resumen de mascotas y tareas de la cuenta activa; muestra hasta seis mascotas por página |
| `mascotas.php` | Crear, consultar, editar y eliminar mascotas |
| `tareas.php` | Crear, consultar, editar y eliminar tareas |
| `problemas.php` | Crear, consultar, editar y eliminar reportes de salud asociados a mascotas |
| `migracion_problemas_salud.sql` | Añade la tabla de reportes de salud a la base existente |
| `migracion_solucion_problemas.sql` | Añade la solución o tratamiento a los reportes de salud existentes |
| `logout.php` | Cierre de sesión |
| `auth.php` | Funciones compartidas de autenticación, sesión, CSRF y escape HTML |
| `conexion\conexion.php` | Conexión PDO con MySQL |
| `style.css` | Estilos responsive |
| `uploads\pets\.htaccess` | Desactiva listados y ejecución de scripts en la carpeta de imágenes |

## Modelo de datos

```text
users 1 ─── N pets 1 ─── N tasks
  └────────────────────── N tasks
              └────────── N health_problems
```

- `pets.user_id` identifica al propietario de cada mascota.
- `tasks.user_id` identifica al propietario y `tasks.pet_id` a la mascota relacionada.
- La clave foránea compuesta de `tasks (user_id, pet_id)` y `pets (user_id, id)` impide asociar una tarea a la mascota de otra cuenta.
- Las claves foráneas eliminan las mascotas y tareas relacionadas al borrar su usuario; al borrar una mascota también se eliminan sus tareas.
- `health_problems` almacena la fecha del reporte, la descripción, la solución o tratamiento indicado y el estado (`pending`, `in_review` o `resolved`); cada reporte queda asociado a una mascota y al mismo usuario propietario.
- La solución o tratamiento queda documentado por el veterinario responsable para dar seguimiento al caso.

## Demostración sugerida

1. Registra una cuenta y muestra que puedes cerrar sesión y volver a entrar.
2. En el panel, crea una mascota con tipo, raza, fecha, sexo y notas.
3. Edita la mascota y confirma que los cambios aparecen en el panel.
4. Crea una tarea para esa mascota, con categoría, fecha, hora y prioridad.
5. Edita la tarea y cambia su estado a completada.
6. Registra un problema de salud para la mascota y cambia su estado durante el seguimiento.
7. Elimina una tarea, un reporte y, si corresponde, una mascota; confirma que ya no aparecen.
8. Registra una segunda cuenta y comprueba que no ve los datos de la primera.
9. Cierra sesión.

## Calidad y seguridad implementadas

- Consultas PDO preparadas para las operaciones que usan valores del usuario.
- Hash de contraseñas con `password_hash()` y validación con `password_verify()`.
- Protección CSRF en formularios que modifican datos.
- Verificación de sesión y de propiedad al consultar, modificar o eliminar mascotas, tareas y reportes de salud.
- Escape de valores al mostrarlos en HTML.
- Validación del servidor para campos requeridos, longitudes, fechas, hora, prioridad y estado.
- Diseño adaptable a pantallas pequeñas y grandes.
- Paginación de mascotas en el panel, con seis tarjetas por página; las tareas y el acceso a reportes permanecen en el dashboard.
- Carga de imágenes con límite de tamaño, validación del MIME real y nombre de archivo aleatorio; la carpeta de imágenes no permite ejecutar scripts.

## Uso del IDE con asistencia de IA

Durante el desarrollo se usó VS Code con asistencia de Copilot para generar y completar formularios PHP, revisar la integración PDO, depurar errores de rutas y de tablas, y preparar instrucciones de uso. Las propuestas se contrastaron con los archivos reales, el comando `php -l` de XAMPP y la aplicación ejecutándose en el navegador.

La IA acelera tareas repetitivas y ayuda a explicar errores, pero puede proponer rutas, nombres de base de datos o soluciones que no coinciden con la instalación. Por eso se comprobaron las rutas de XAMPP, el nombre `veterinaria petca`, las tablas y las páginas en ejecución antes de considerar terminado el cambio.

## Comparación del IDE: VS Code con Copilot y PhpStorm con AI Assistant

Esta comparación describe capacidades generales; la experiencia personal y la versión de las herramientas pueden variar.

| Aspecto | VS Code con Copilot | PhpStorm con AI Assistant |
| --- | --- | --- |
| Asistencia para código | Sugerencias y conversación dentro del editor; útil para completar formularios y explicar errores. | Asistencia integrada con el IDE y contexto de proyecto; útil junto con sus herramientas de navegación y análisis. |
| PHP y navegación | Ligero y flexible; puede requerir extensiones para completar el análisis y la depuración de PHP. | Enfocado en PHP e incluye navegación y análisis de código integrados. |
| Configuración | Se adapta mediante extensiones; puede requerir configurar PHP, depuración y herramientas adicionales. | Ofrece una experiencia PHP más integrada, aunque algunas funciones dependen de la edición y configuración. |
| Ventaja principal | Flexibilidad, rapidez y ecosistema amplio de extensiones. | Herramientas especializadas para entender y mantener proyectos PHP grandes. |
| Desventaja a considerar | La calidad del análisis depende de las extensiones instaladas y configuradas. | Puede consumir más recursos y la licencia o funciones disponibles dependen del producto contratado. |

Para esta aplicación pequeña con PHP nativo y XAMPP, VS Code con Copilot permitió iterar rápido y probar directamente en el navegador. Para un proyecto PHP de mayor tamaño, conviene comparar también la navegación, depuración y análisis estático del IDE elegido por el equipo.

Antes de presentar, completa con tu experiencia:

- ¿Qué sugerencia de la IA te ahorró más tiempo?
- ¿Qué respuesta tuviste que corregir o verificar?
- ¿Qué IDE prefieres para el siguiente proyecto y por qué?

## Guion breve para exponer

1. **Problema:** centralizar la información de mascotas y recordar sus cuidados.
2. **Tecnologías:** PHP nativo, PDO, MySQL en XAMPP y HTML/CSS responsive.
3. **Relaciones:** un usuario tiene mascotas y tareas; cada tarea corresponde a una mascota del mismo usuario.
4. **Demostración:** registrar e iniciar sesión, crear una mascota y una tarea, editar, completar y eliminar.
5. **Buenas prácticas:** contraseñas con hash, consultas preparadas, validación, escape HTML y protección CSRF.
6. **IDE e IA:** explicar cómo Copilot ayudó, qué verificaciones se hicieron y qué respuesta se tuvo que revisar.
7. **Cierre:** mencionar una mejora futura, por ejemplo agregar filtros y recordatorios.
