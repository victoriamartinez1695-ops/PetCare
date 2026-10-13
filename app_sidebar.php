<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$currentSection = isset($_GET['section']) && is_string($_GET['section'])
    ? $_GET['section']
    : 'home';
$navigationItems = [
    [
        'href' => 'dashboard.php',
        'label' => 'Inicio',
        'page' => 'dashboard.php',
        'section' => 'home',
        'icon' => 'm3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z',
    ],
    [
        'href' => 'dashboard.php?section=pets#mascotas',
        'label' => 'Mascotas',
        'page' => 'dashboard.php',
        'section' => 'pets',
        'icon' => 'M12 21c-3.7 0-7-2.2-7-5 0-2 1.8-3.5 4-3.5 1.1 0 2 .4 3 1.1 1-.7 1.9-1.1 3-1.1 2.2 0 4 1.5 4 3.5 0 2.8-3.3 5-7 5ZM6.5 10C5.1 10 4 8.7 4 7s1.1-3 2.5-3S9 5.3 9 7s-1.1 3-2.5 3Zm11 0C16.1 10 15 8.7 15 7s1.1-3 2.5-3S20 5.3 20 7s-1.1 3-2.5 3ZM11 8c-1.4 0-2.5-1.3-2.5-3S9.6 2 11 2s2.5 1.3 2.5 3S12.4 8 11 8Zm-5 5c-1.4 0-2.5-1.3-2.5-3S4.6 7 6 7s2.5 1.3 2.5 3S7.4 13 6 13Z',
    ],
    [
        'href' => 'tareas.php',
        'label' => 'Tareas',
        'page' => 'tareas.php',
        'section' => null,
        'icon' => 'M8 3h8v2h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h3V3Zm2 2h4V5h-4Zm-.4 11.2-2.1-2.1-1.4 1.4 3.5 3.5 6.3-6.3-1.4-1.4-4.9 4.9Z',
    ],
    [
        'href' => 'problemas.php',
        'label' => 'Reportes de salud',
        'page' => 'problemas.php',
        'section' => null,
        'icon' => 'M6 3h8l5 5v12a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm7 1.5V9h4.5ZM8 13h8v1.8H8Zm0 4h8v1.8H8Z',
    ],
];
?>
<aside class="app-sidebar" aria-label="Navegación principal">
    <a class="sidebar-brand" href="dashboard.php" aria-label="PetCare, inicio">
        <svg viewBox="0 0 48 48" aria-hidden="true" focusable="false">
            <path d="M16.1 21.2c-3.6 0-6.3-3.2-6.3-7.2 0-3.2 1.8-5.6 4.3-5.6 3.5 0 6.4 4.7 6.4 8 0 2.7-1.8 4.8-4.4 4.8Zm15.8 0c-2.6 0-4.4-2.1-4.4-4.8 0-3.3 2.9-8 6.4-8 2.5 0 4.3 2.4 4.3 5.6 0 4-2.7 7.2-6.3 7.2ZM8.7 29.1c-2.5 0-4.2-2-4.2-4.8 0-3.4 2.6-7.1 5.6-7.1 2.1 0 3.6 1.9 3.6 4.4 0 3.7-2.3 7.5-5 7.5Zm30.6 0c-2.7 0-5-3.8-5-7.5 0-2.5 1.5-4.4 3.6-4.4 3 0 5.6 3.7 5.6 7.1 0 2.8-1.7 4.8-4.2 4.8ZM24 43c-5.2 0-12-3.4-12-9.2 0-5.2 5.1-10.5 12-10.5s12 5.3 12 10.5C36 39.6 29.2 43 24 43Z"/>
        </svg>
        <span>PetCare</span>
    </a>
    <nav class="sidebar-nav">
        <?php foreach ($navigationItems as $item): ?>
            <?php
            $isCurrentPage = $currentPage === $item['page']
                && ($item['section'] === null || $currentSection === $item['section']);
            ?>
            <a
                class="sidebar-nav-link<?= $isCurrentPage ? ' is-active' : '' ?>"
                href="<?= e($item['href']) ?>"
                <?= $isCurrentPage ? 'aria-current="page"' : '' ?>
            >
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?= e($item['icon']) ?>"/></svg>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <form class="sidebar-logout" method="post" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="sidebar-nav-link" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 3h9a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-9v-2h9V5h-9V3ZM4 11h9v2H4v3l-4-4 4-4v3Z"/></svg>
            <span>Cerrar sesión</span>
        </button>
    </form>
</aside>
