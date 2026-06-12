<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function navItem(string $href, string $icon, string $label, string $current): string {
    $active = basename($href) === $current ? ' active' : '';
    return sprintf(
        '<a href="%s" class="sidebar-item%s"><i class="ti %s" aria-hidden="true"></i><span>%s</span></a>',
        SITE_URL . '/admin/' . $href, $active, $icon, $label
    );
}
?>
<aside class="admin-sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark" style="width:36px;height:36px;font-size:15px;flex-shrink:0">E</div>
    <div>
      <div style="font-weight:500;font-size:14px;color:#FAEEDA"><?= SITE_NAME ?></div>
      <div style="font-size:11px;color:#EF9F27">Administration</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <p class="sidebar-section-label">Général</p>
    <?= navItem('index.php',          'ti-layout-dashboard', 'Dashboard',          $currentPage) ?>

    <p class="sidebar-section-label">Contenu pédagogique</p>
    <?= navItem('courses.php',        'ti-school',           'Cours',              $currentPage) ?>
    <?= navItem('course_add.php',     'ti-book-plus',        'Ajouter un cours',   $currentPage) ?>

    <?php
    // Breadcrumb contextuel : Modules d'un cours
    $courseIdCtx = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
    $moduleIdCtx  = (int)($_GET['module_id']  ?? $_POST['module_id']  ?? 0);

    $pagesModules  = ['modules.php','module_add.php','module_edit.php'];
    $pagesSequences = ['sequences.php','sequence_add.php','sequence_edit.php'];

    if (in_array($currentPage, $pagesModules) && $courseIdCtx):
    ?>
    <a href="<?= SITE_URL ?>/admin/modules.php?course_id=<?= $courseIdCtx ?>"
       class="sidebar-item<?= in_array($currentPage, $pagesModules) ? ' active' : '' ?>">
      <i class="ti ti-layout-list"></i><span>Modules</span>
    </a>
    <?php endif; ?>

    <?php if (in_array($currentPage, $pagesSequences) && $moduleIdCtx): ?>
    <a href="<?= SITE_URL ?>/admin/sequences.php?module_id=<?= $moduleIdCtx ?>"
       class="sidebar-item<?= in_array($currentPage, $pagesSequences) ? ' active' : '' ?>">
      <i class="ti ti-list-numbers"></i><span>Séquences</span>
    </a>
    <?php endif; ?>

    <p class="sidebar-section-label">Communauté</p>
    <?= navItem('users.php',          'ti-users',            'Utilisateurs',       $currentPage) ?>

    <p class="sidebar-section-label">Site</p>
    <?= navItem('slides.php',         'ti-photo',            'Slides accueil',     $currentPage) ?>
    <?= navItem('slide_add.php',      'ti-photo-plus',       'Ajouter slide',      $currentPage) ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= SITE_URL ?>" target="_blank" class="sidebar-item" style="font-size:12px">
      <i class="ti ti-external-link"></i><span>Voir le site</span>
    </a>
    <a href="<?= SITE_URL ?>/admin/logout.php" class="sidebar-item" style="color:#F0997B;font-size:12px">
      <i class="ti ti-logout"></i><span>Déconnexion</span>
    </a>
  </div>
</aside>
