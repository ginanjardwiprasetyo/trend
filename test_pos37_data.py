import subprocess
import json
output = subprocess.check_output([
    "/Applications/XAMPP/xamppfiles/bin/php", "-r",
    """
    $_POST = json_decode('{"pos_id":37,"dataType":"tahunan","aggregation":"sum","yearFrom":1980,"yearTo":2024,"month":"all"}', true);
    include '/Applications/XAMPP/xamppfiles/htdocs/project01/php/get_timeseries.php';
    """
])
print(output.decode('utf-8'))
