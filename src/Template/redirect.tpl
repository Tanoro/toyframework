<script type="text/javascript">
timeout = ({$redirect.delay} * 10);
function maj_redirect()
{
	timer = setTimeout('maj_redirect();', 100);
	if (timeout > 0)
	{
		timeout -= 1;
	}
	else
	{
		clearTimeout(timer);
		window.location = "{$redirect.url}";
	}
}
maj_redirect();
</script>