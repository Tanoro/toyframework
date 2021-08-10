{extends file="template.tpl"}
{block name=output}

<div style="width: {$dialogue.width}; margin-right: auto; margin-left: auto;">
	<div class="pseudoTab">{$dialogue.title}</div>
	<div class="fieldsection flattop">
		<blockquote><p class="general" style="font-size: 20px;">{$dialogue.string}</p></blockquote>
	</div>
</div>

{/block}
