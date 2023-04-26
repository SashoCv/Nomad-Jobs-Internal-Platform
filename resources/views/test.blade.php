<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="https://nomad-cloud.in/api/companyStore" method="POST" enctype="multipart/form-data">

        <input type="file" name="companyLogo">
        <input type="text" placeholder="nameOfCompany" name="nameOfCompany">
        <input type="text" placeholder="address" name="address">
        <input type="text" placeholder="email" name="email">
        <input type="text" placeholder="website" name="website">
        <input type="text" placeholder="phoneNumber" name="phoneNumber">
        <input type="text" placeholder="EIK" name="EIK">
        <input type="text" placeholder="contactPerson" name="contactPerson">

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