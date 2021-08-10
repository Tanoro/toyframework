{extends file="template.tpl"}
{block name=output}

	<div class="pseudoTab">Add Setting</div>
	<div class="fieldsection flattop">
		<table class="default">
			<thead>
				<tr align="left" valign="top">
					<th>Setting</th>
					<th>Value</th>
					<th></th>
				</tr>
			</thead>
			<tbody id="addSettingForm">
				<tr valign="top">
					<td><input type="text" name="setting" value="" placeholder="Setting Name" /></td>
					<td><input type="text" name="descrip" value="" size="60" placeholder="Setting description..." /><br>{$row.descrip}</td>
					<td></td>
				</tr>
				<tr valign="top">
					<td><input type="text" name="hook" value="" placeholder="setting_hook" /></td>
					<td><textarea name="data" cols="60" rows="5"></textarea></td>
					<td><input type="button" name="addSetting" value="Add Setting" /></td>
				</tr>
			</tbody>
		</table>
	</div>

	{if $rows}
		<div class="pseudoTab">Settings</div>
		<div class="fieldsection flattop">
			<table class="default tableSettings">
				<thead>
					<tr align="left" valign="top">
						<th>Setting</th>
						<th>Hook</th>
						<th>Value</th>
					</tr>
				</thead>
				<tbody>
				{foreach $rows as $row}
					<tr valign="top">
						<td>{$row.setting}</td>
						<td>{$row.hook}</td>
						<td>
							{if $row.hook == 'activeProfile'}
								{$row.data}<br>
							{else}
								<textarea name="{$row.hook}" cols="60" rows="3" class="saveSetting">{$row.data}</textarea>
								<img src="/images/check-ok-trans.png" class="okCheck" ok="{$row.hook}" /><br>
							{/if}
							{$row.descrip}
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>
	{else}
		{*There are no keywords to scrape*}
		There are no system settings
	{/if}
{/block}