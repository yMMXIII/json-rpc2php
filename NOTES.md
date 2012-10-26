Notes on scripts
================

jsonRPC2Server.php
------------------

1. Batch implementing
---------------------

Principe:

1. requests comes in

2. Checks if it's an assoc array

3. if it is, do nothing

4. Else, convert it to a batch request, containing only one request.

5. on returning the result(s), check if the assoc array only has one value,

6. Ifso, only return this value.

7. else, return the whole batch array