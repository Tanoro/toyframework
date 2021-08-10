{extends file="template.tpl"}
{block name=output}

	<div class="pseudoTab">Backups</div>
	<div class="fieldsection flattop">
		<form action="/{$pageinfo.slug}" method="post">
			<input type="button" name="execMigrations" value="Run Migrations" class="lgButton" style="float: right;" />
			<input type="submit" name="makeBackup" value="Backup Now" class="lgButton" />
		</form>
		<table class="default">
			<thead>
			<tr>
				<th align="left">File</th>
				<th>Date</th>
				<th>Size</th>
				<th>Action</th>
			</tr>
			</thead>
			<tbody>
			{foreach $rows as $row}
				<tr align="center">
					<td align="left"><a href="{$row.path}" target="_blank">{$row.file}</a></td>
					<td>{$row.moddate|date_format:"%B, %e, %Y - %I:%M %p"}</td>
					<td>{$row.filesize}</td>
					<td>
						<input type="button" class="restoreBackup" value="Restore" row="{$row.file}" />
						<input type="button" class="delBackup" value="Delete" row="{$row.file}" />
					</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="4" align="center">No rows</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	</div>
{/block}
