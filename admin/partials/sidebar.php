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
      <div style="font-weight:500;font-size:14px;color:#EDE9FE"><?= SITE_NAME ?></div>
      <div style="font-size:11px;color:#8B5CF6">Administration</div>
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

    <p class="sidebar-section-label">Quiz & Évaluations</p>
    <?= navItem('quizzes.php',           'ti-help-circle',       'Quiz',                $currentPage) ?>
    <?= navItem('quiz_add.php',          'ti-circle-plus',       'Ajouter un quiz',     $currentPage) ?>
    <?= navItem('review_center.php',     'ti-clipboard-check',   'Review Center',       $currentPage) ?>
    <?= navItem('gradebook.php',         'ti-table',             'Gradebook',           $currentPage) ?>

    <p class="sidebar-section-label">Paiements</p>
    <?= navItem('payments.php',          'ti-credit-card',       'Paiements',           $currentPage) ?>

    <p class="sidebar-section-label">Bibliothèque</p>
    <?= navItem('library.php',           'ti-library',           'Ressources',          $currentPage) ?>
    <?= navItem('library_add.php',       'ti-file-plus',         'Ajouter ressource',   $currentPage) ?>

    <p class="sidebar-section-label">Communauté</p>
    <?= navItem('users.php',             'ti-users',             'Utilisateurs',        $currentPage) ?>
    <?= navItem('forum.php',             'ti-messages',          'Forum',               $currentPage) ?>
    <?= navItem('cohorts.php',           'ti-users-group',       'Cohortes',            $currentPage) ?>
    <?= navItem('tags.php',              'ti-tag',               'Tags',                $currentPage) ?>

    <p class="sidebar-section-label">Gamification</p>
    <?= navItem('badges.php',            'ti-award',             'Badges',              $currentPage) ?>
    <?= navItem('challenges.php',        'ti-trophy',            'Défis hebdo',         $currentPage) ?>

    <p class="sidebar-section-label">Blog</p>
    <?= navItem('blog.php',              'ti-news',              'Articles',            $currentPage) ?>
    <?= navItem('blog_add.php',          'ti-file-plus',         'Nouvel article',      $currentPage) ?>

    <p class="sidebar-section-label">Certifications</p>
    <?= navItem('certificates.php',      'ti-certificate',       'Certificats',         $currentPage) ?>

    <p class="sidebar-section-label">Import / Export</p>
    <?= navItem('import_users.php',      'ti-file-import',       'Importer utilisateurs',$currentPage) ?>
    <?= navItem('export_users.php',      'ti-download',          'Exporter utilisateurs',$currentPage) ?>

    <p class="sidebar-section-label">Ventes & Promo</p>
    <?= navItem('promo_codes.php',       'ti-ticket',            'Codes promo',         $currentPage) ?>
    <?= navItem('automations.php',       'ti-robot',             'Automations',         $currentPage) ?>

    <p class="sidebar-section-label">Coaching & Intégrations</p>
    <?= navItem('coaching.php',          'ti-video',             'Sessions coaching',   $currentPage) ?>
    <?= navItem('settings.php',          'ti-settings',          'Paramètres',          $currentPage) ?>
    <a href="<?= SITE_URL ?>/api/docs.php" target="_blank" class="sidebar-item" style="font-size:12px">
      <i class="ti ti-api" aria-hidden="true"></i><span>Documentation API</span>
    </a>

    <p class="sidebar-section-label">SEO & Sitemap</p>
    <a href="<?= SITE_URL ?>/sitemap.php" target="_blank" class="sidebar-item">
      <i class="ti ti-sitemap" aria-hidden="true"></i><span>Voir le sitemap</span>
    </a>

    <p class="sidebar-section-label">Statistiques & Site</p>
    <?= navItem('analytics.php',         'ti-chart-area',        'Analytics',           $currentPage) ?>
    <?= navItem('stats.php',             'ti-chart-bar',         'Statistiques',        $currentPage) ?>
    <?= navItem('audit_log.php',         'ti-shield-check',      'Journal d\'audit',    $currentPage) ?>
  
    <div class="sidebar-section">NOUVEAUTÉS</div>
    <a href="<?= SITE_URL ?>/admin/messages.php" class="sidebar-link <?= ($currentPage??'')==='messages.php'?'active':'' ?>">
      <i class="ti ti-messages"></i> <span>Messagerie</span>
    </a>
    <a href="<?= SITE_URL ?>/admin/question_bank.php" class="sidebar-link <?= ($currentPage??'')==='question_bank.php'?'active':'' ?>">
      <i class="ti ti-database"></i> <span>Banque de questions</span>
    </a>
    <a href="<?= SITE_URL ?>/admin/satisfaction_results.php" class="sidebar-link <?= ($currentPage??'')==='satisfaction_results.php'?'active':'' ?>">
      <i class="ti ti-mood-smile"></i> <span>Satisfaction</span>
    </a>
    <a href="<?= SITE_URL ?>/admin/contact_messages.php" class="sidebar-link <?= ($currentPage??'')==='contact_messages.php'?'active':'' ?>">
      <i class="ti ti-mail"></i> <span>Messages contact</span>
    </a>


    <div class="sidebar-section">GESTION AVANCÉE</div>
    <a href="<?= SITE_URL ?>/admin/bundles.php" class="sidebar-link <?= ($currentPage??'')==='bundles.php'?'active':'' ?>">
      <i class="ti ti-package"></i> <span>Bundles / Packs</span>
    </a>
    <a href="<?= SITE_URL ?>/admin/invoices.php" class="sidebar-link <?= ($currentPage??'')==='invoices.php'?'active':'' ?>">
      <i class="ti ti-receipt"></i> <span>Factures</span>
    </a>
    <a href="<?= SITE_URL ?>/admin/email_campagnes.php" class="sidebar-link <?= ($currentPage??'')==='email_campagnes.php'?'active':'' ?>">
      <i class="ti ti-mail-forward"></i> <span>Emails ciblés</span>
    </a>
    <a href="<?= SITE_URL ?>/admin/rapport_cohorte.php" class="sidebar-link <?= ($currentPage??'')==='rapport_cohorte.php'?'active':'' ?>">
      <i class="ti ti-report"></i> <span>Rapport cohorte</span>
    </a>


    <a href="<?= SITE_URL ?>/admin/course_import.php" class="sidebar-link <?= ($currentPage??'')==='course_import.php'?'active':'' ?>">
      <i class="ti ti-file-import"></i> <span>Import cours JSON</span>
    </a>


    <a href="<?= SITE_URL ?>/admin/coaches.php" class="sidebar-link <?= ($currentPage??'')==='coaches.php'?'active':'' ?>">
      <i class="ti ti-star"></i> <span>Gestion coachs</span>
    </a>

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
