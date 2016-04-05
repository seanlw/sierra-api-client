# Sierra PHP API Class

This PHP class allows you to connect to Sierra's API and make queries.

## Example Usage

The example gets information on bib ID 3996024 and limits the results to 20 records only including the fields id, location, and status.

```
include('Sierra.php');

$s = new Sierra(array(
  'endpoint' => 'Sierra REST API Endpoint (ie https://lib.example.edu/iii/sierra-api/v1/)',
  'key' => 'Sierra Client Key',
  'secret' => 'Sierra Client Secret'
  'tokenFile' => 'Location to the temp file to keep token infomation, default: /tmp/SierraToken'
 ));
 
 $bibInformation = $s->query('items', array(
   'bibIds' => '3996024',
  'limit' => '20',
  'fields' => 'id,location,status'
));
```

#### License [MIT License](LICENSE.txt)