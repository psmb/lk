<?php
//
// Direct link for donations, used by LTSO
//

if (!$_GET['amount'] || !$_GET['email']) {
	die('Неверная ссылка');
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Пожертвование на СФИ</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="https://widget.cloudpayments.ru/bundles/cloudpayments"></script>
</head>
<body>
	<script>
var widget = new cp.CloudPayments();
widget.charge(
	{
		publicId: 'pk_1a899983c4a2b2b6c7be8ecac65cf',
		description: 'Пожертвование на Свято-Филаретовский православно-христианский институт',
		amount: <?=$_GET['amount']?>,
		currency: 'RUB',
		accountId: "<?=$_GET['email']?>",
		data: {
			contextid: 'ltso',
			referer: 'projects'
		}
	},
	'https://sfi.ru/support/thanks.html',
	function (reason, options) {
		alert('Ошибка' + reason);
	}
);
	</script>
</body>
</html>
