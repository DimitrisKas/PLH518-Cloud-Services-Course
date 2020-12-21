let error = true;

let res = [
    use Project2,
    db.Users.createIndex({ id: 1 }),
    db.Movies.createIndex({ id: 1 }),
    db.Favorites.createIndex({ id: 1 }),
    db.Cinemas.createIndex({ id: 1 })

]

printjson(res)

if (error) {
    print('Error, exiting')
    quit(1)
}