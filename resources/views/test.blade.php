<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="http://127.0.0.1:8001/api/storeUser" method="POST">

        @csrf
        <input type="text" placeholder="firstName" name="firstName">
        <input type="text" placeholder="lastName" name="lastName">
        <input type="text" placeholder="email" name="email">
        <input type="text" placeholder="password" name="password">
        <input type="text" placeholder="role_id" name="role_id">
        <input type="text" placeholder="company_id" name="company_id">

        <button type="submit">Submit</button>
    </form>
</body>

</html>