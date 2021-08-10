<!DOCTYPE html>
<html lang="en-US">
<head>
	<title>{$pageinfo.title}</title>

	{foreach $embedstyles as $styles}
	<link rel="stylesheet" type="text/css" href="{$styles}">
	{/foreach}

	<script src="https://unpkg.com/react@17/umd/react.development.js" crossorigin></script>
	<script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js" crossorigin></script>
	{foreach $embedjs as $js}
	<script language="JavaScript" type="text/javascript" src="{$js}"></script>
	{/foreach}
</head>
<body>

<div id="pageMenu">
	<h3>{$profile.name}</h3>
	{include 'menu.tpl'}
</div>

{if $system_warning !== null}<div class="warningBox" style="margin-bottom: 10px;"><strong>Warning:</strong> {$system_warning}</div>{/if}

<div id="pageBody">
	{block name=output}{/block}
</div>

</body>
</html>
