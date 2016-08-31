<?php
$this->title = 'Парсинг';
?>

<?php if ($isLoading): ?>
	<h1>Пожалуйста подождите</h1>
	<p>Выполняется обработка данных</p>
	<script type="text/javascript">
		setTimeout(function(){
			window.location.reload();
		}, 15000);
	</script>
<?php else: ?>
	<h1>Результат</h1>
	<a href="./csv/parsed.csv" target="_blank" class="btn btn-success btn-lg">Скачать</a>
<?php endif; ?>