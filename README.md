# PHP Punchout Plugin Starter Kit


- Each page on the shop should include in the ``<head>`` section the following script

````
<script type="application/javascript src="/punchout?action=script"></script>
````

- In the code ``punchout_id`` is saved in ``$_SESSION``, but preferably it should be saved in an idiomatic way so that it is removed if a user logs out for example.
