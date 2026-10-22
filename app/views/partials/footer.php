    </section>
</main>
</div>

<?php require __DIR__ . '/modal.php'; ?>

<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="public/js/app.js?v=<?= @filemtime(__DIR__ . '/../../public/js/app.js') ?: time(); ?>"></script>
</body>
</html>
