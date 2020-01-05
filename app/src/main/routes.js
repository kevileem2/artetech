const express = require("express")
const bodyParser = require("body-parser")
const mysql = require("mysql")

const connection = mysql.createPool({
  host: "localhost",
  user: "root",
  password: "root",
  database: "artetech",
  port: "8889"
})

const app = express()

const allowCrossDomain = (req, res, next) => {
  res.header("Access-Control-Allow-Origin", "*")
  res.header("Access-Control-Allow-Methods", "GET,PUT,POST,DELETE")
  res.header("Access-Control-Allow-Headers", "Content-Type")

  next()
}

app.use(allowCrossDomain)
// Creating a GET route that returns data from the 'user' table.
app.get("/users", (req, res) => {
  // Connecting to the database.
  connection.getConnection((err, connection) => {
    // Executing the MySQL query (select all data from the 'users' table).
    connection.query("SELECT * FROM employee", (error, results, fields) => {
      // If some error occurs, we throw an error.
      if (error) throw error

      // Getting the 'response' from the database and sending it to our route. This is were the data is.
      res.send(results)
    })
  })
})

app.get("/clients", (req, res) => {
  // Connecting to the database.
  connection.getConnection((err, connection) => {
    // Executing the MySQL query (select all data from the 'users' table).
    connection.query("SELECT * FROM client", (error, results, fields) => {
      // If some error occurs, we throw an error.
      if (error) throw error

      // Getting the 'response' from the database and sending it to our route. This is were the data is.
      res.send(results)
    })
  })
})

app.get("/terms", (req, res) => {
  // Connecting to the database.
  connection.getConnection((err, connection) => {
    // Executing the MySQL query (select all data from the 'users' table).
    connection.query("SELECT * FROM term", (error, results, fields) => {
      // If some error occurs, we throw an error.
      if (error) throw error

      // Getting the 'response' from the database and sending it to our route. This is were the data is.
      res.send(results)
    })
  })
})

app.get("/projects", (req, res) => {
  // Connecting to the database.
  connection.getConnection((err, connection) => {
    // Executing the MySQL query (select all data from the 'users' table).
    connection.query("SELECT * FROM project", (error, results, fields) => {
      // If some error occurs, we throw an error.
      if (error) throw error

      // Getting the 'response' from the database and sending it to our route. This is were the data is.
      res.send(results)
    })
  })
})

// Starting our server.
app.listen(3000, () => {
  console.log("Go to http://localhost:3000/users so you can see the data.")
})
