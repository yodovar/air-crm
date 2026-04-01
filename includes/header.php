<?php
$role = $_SESSION['user_role'] ?? '';
$isAdmin = ($role === 'admin');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<style>
.page-header{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem}
.burger-btn{width:44px;height:44px;padding:0;border:none;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);cursor:pointer;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:5px;-webkit-tap-highlight-color:transparent}
.burger-btn span{display:block;width:18px;height:2px;background:#333;border-radius:1px}
.page-title{font-size:1.25rem;font-weight:600;flex:1;margin:0}
.burger-nav{position:fixed;top:0;left:0;right:0;bottom:0;z-index:1000;pointer-events:none}
.burger-nav.open{pointer-events:auto}
.burger-nav-overlay{position:absolute;inset:0;background:rgba(0,0,0,.3);opacity:0;transition:opacity .2s}
.burger-nav.open .burger-nav-overlay{opacity:1}
.burger-nav-panel{position:absolute;top:0;left:0;bottom:0;width:280px;max-width:85vw;background:#fff;box-shadow:4px 0 20px rgba(0,0,0,.15);transform:translateX(-100%);transition:transform .25s;padding:1rem;padding-top:calc(1rem + env(safe-area-inset-top));display:flex;flex-direction:column;gap:.25rem;overflow-y:auto}
.burger-nav.open .burger-nav-panel{transform:translateX(0)}
.burger-nav-panel a{padding:1rem;border-radius:8px;text-decoration:none;color:#333;font-weight:500;-webkit-tap-highlight-color:transparent}
.burger-nav-panel a:hover,.burger-nav-panel a:active{background:#f0f0f0}
.burger-nav-panel a.active{background:#333;color:#fff}
.burger-nav-panel .burger-logout{color:#c62828;margin-top:auto}
body.nav-open{overflow:hidden}
</style>
<header class="page-header">
    <button type="button" class="burger-btn" id="burgerBtn" aria-label="Меню">
        <span></span><span></span><span></span>
    </button>
    <h1 class="page-title"><?= $pageTitle ?? 'CRM' ?></h1>
</header>
<nav class="burger-nav" id="burgerNav">
    <div class="burger-nav-overlay" id="burgerOverlay"></div>
    <div class="burger-nav-panel">
        <a href="<?= url('dashboard.php') ?>" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Главная</a>
        <?php if ($isAdmin): ?>
        <a href="<?= url('requests.php') ?>" class="<?= $currentPage === 'requests' ? 'active' : '' ?>">Запросы</a>
        <a href="<?= url('cashbox.php') ?>" class="<?= $currentPage === 'cashbox' ? 'active' : '' ?>">История кассы</a>
        <?php endif; ?>
        <a href="<?= url('orders.php') ?>" class="<?= $currentPage === 'orders' ? 'active' : '' ?>"><?= $isAdmin ? 'Заказы' : 'Мои заказы' ?></a>
        <?php if ($isAdmin): ?>
        <a href="<?= url('orders_create.php') ?>" class="<?= $currentPage === 'orders_create' ? 'active' : '' ?>">Создать заказ</a>
        <a href="<?= url('products.php') ?>" class="<?= $currentPage === 'products' ? 'active' : '' ?>">Товары</a>
        <a href="<?= url('products_add.php') ?>" class="<?= $currentPage === 'products_add' ? 'active' : '' ?>">Добавить товар</a>
        <?php endif; ?>
        <a href="<?= url('logout.php') ?>" class="burger-logout">Выйти</a>
    </div>
</nav>
<script>
(function() {
    var btn = document.getElementById('burgerBtn');
    var nav = document.getElementById('burgerNav');
    var overlay = document.getElementById('burgerOverlay');
    if (btn && nav) {
        function toggle() { nav.classList.toggle('open'); document.body.classList.toggle('nav-open', nav.classList.contains('open')); }
        function close() { nav.classList.remove('open'); document.body.classList.remove('nav-open'); }
        btn.addEventListener('click', toggle);
        overlay.addEventListener('click', close);
        nav.querySelectorAll('a:not(.burger-logout)').forEach(function(a) { a.addEventListener('click', close); });
    }
})();
</script>
