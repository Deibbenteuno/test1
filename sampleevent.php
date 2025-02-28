<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    
<form action="#" id="form">
    <input type="text" id="barcode">
</form>

<input type="text" name="" id="sa">

<script>
$(document).ready(function() {

    $('#sa').on('keyup', function(e) {
        console.log(e.key, e.keyCode)
    })




    $('#barcode').focus();
    // When quantity changes, send the updated quantity to the server using AJAX
    $("#form").submit(function(e){
        e.preventDefault();        

        console.log($('#barcode').val());

        $('#barcode').val('');
    });
});
</script>
</body>
</html>