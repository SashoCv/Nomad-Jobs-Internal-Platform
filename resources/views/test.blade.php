<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="https://nomad-cloud.in/api/file" method="POST" enctype="multipart/form-data">

        <input type="file" name="file">
        <input type="text" placeholder="category_id" name="category_id">
        <input type="text" placeholder="candidate_id" name="candidate_id">

        <button type="submit">Submit</button>
    </form>
</body>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    axios({
            method: 'get',
            url: 'https://nomad-cloud.in/api/companies',
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