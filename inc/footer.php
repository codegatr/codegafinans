        </div>
    </main>
</div>

<?php /* Mobil alt nav - sadece kullanici girisi yapildiysa ve ekran <992px ise gosterilir (CSS ile) */ ?>
<?php if (!empty($_SESSION['user_id'])): ?>
<nav class="cf-bottomnav" aria-label="Alt menü">
    <a href="/dashboard.php"    class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'dashboard.php')    ? 'active' : '' ?>" aria-label="Anasayfa">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/></svg>
        <span>Anasayfa</span>
    </a>
    <a href="/transactions.php" class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'transactions.php') ? 'active' : '' ?>" aria-label="Gelir Gider">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
        <span>Gelir/Gider</span>
    </a>
    <a href="/customers.php" class="cf-bottomnav-fab <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'customers.php') ? 'active' : '' ?>" aria-label="Cariler">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
        <span>Cariler</span>
    </a>
    <a href="/debts.php"        class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'debts.php')        ? 'active' : '' ?>" aria-label="Borçlar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 12h20"/></svg>
        <span>Borçlar</span>
    </a>
    <a href="/budgets.php"      class="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'budgets.php')      ? 'active' : '' ?>" aria-label="Bütçe">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <span>Bütçe</span>
    </a>
</nav>
<?php endif; ?>

<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
