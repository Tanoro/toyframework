{extends file="template.tpl"}
{block name=output}

<div class="fieldsection">
<strong>Useragent:</strong> {$smarty.server.HTTP_USER_AGENT}<br>
<strong>IP Address:</strong> {$smarty.server.REMOTE_ADDR}
</div>

<div class="fieldsection">
<strong>Environment:</strong><br>
APP_NAME={$smarty.env.APP_NAME}<br>
APP_ROOT={$smarty.env.APP_ROOT}
</div>

<div class="fieldsection">
	<ul class="system">
	{foreach $status as $row}
	<li><span style="{if $row.alert}color: red; font-weight: bold;{else}color: green;{/if}">{$row.text}</span></li>
	{/foreach}
	</ul>
</div>

{/block}
