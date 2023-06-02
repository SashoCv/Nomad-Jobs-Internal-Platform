<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="http://127.0.0.1:8000/api/storeUser" method="POST" enctype="multipart/form-data">
      
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

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    axios({
            method: 'post',
            url: 'https://nomad-cloud.in/api/storeUser',
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer 31|oGZpYKKnIz9KnEEqGIlDouoFg17796I0xpmZnw1j"
            },
        })
        .then(function(response) {
            console.log(response)
        });
</script>

</html>