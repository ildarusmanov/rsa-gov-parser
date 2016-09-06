<?php
use yii\helpers\Url;

$this->title = 'Парсинг';
?>

<?php if ($isLoading): ?>
	<h1>Пожалуйста подождите</h1>
	<p>Выполняется...<b><?= $stepTitle ?></b></p>
	<a href="<?= Url::toRoute(['stop']) ?>" class="btn btn-lg btn-danger">Отменить</a>
	<script type="text/javascript">
		setTimeout(function(){
			window.location.reload();
		}, 15000);
	</script>
<?php else: ?>
	<h1>Результат</h1>
	<a href="./csv/parsed.csv" target="_blank" class="btn btn-success btn-lg">Скачать</a>
<?php endif; ?>
