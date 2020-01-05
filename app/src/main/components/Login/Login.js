import React, { useState, useEffect } from "react"
import { User, Lock } from "styled-icons/fa-solid"
import {
  BackgroundComponent,
  Container,
  Row,
  ImageLogoComponent
} from "./components"
import axios from "axios"
import bcrypt from "bcryptjs"

export default () => {
  const [emailValue, setEmailValue] = useState("")
  const [passValue, setPassValue] = useState("")
  const [users, setUsers] = useState([])

  useEffect(() => {
    fetchUsers()
  }, [])

  const fetchUsers = () => {
    axios
      .get("http://localhost:3000/users")
      .then(res => res)
      .then(data => setUsers(data.data))
  }
  const handleEmailValue = e => {
    setEmailValue(e.target.value)
  }
  const handlePassValue = e => {
    setPassValue(e.target.value)
  }

  const handleSubmit = () => {
    users.forEach(element => {
      if ((element.email = emailValue)) {
        bcrypt.compare(passValue, element.password, (err, isMatch) => {
          if (err) {
            console.log(err)
          } else if (!isMatch) {
            console.log("Password Doesn't match")
          } else {
            console.log("password match")
            localStorage.setItem("authentication", JSON.stringify(element))
          }
        })
      }
    })
  }
  return (
    <BackgroundComponent>
      <Container>
        <Row>
          <ImageLogoComponent src={require("../../assets/images/logo.png")} />
          <form style={{ marginTop: "10%" }} onSubmit={handleSubmit}>
            <User style={{ paddingTop: "15px" }} size="24" color="#029875" />
            <input
              type="email"
              style={{
                marginTop: "10px",
                marginLeft: "10%",
                width: "79%",
                height: "24px",
                borderColor: "#029875",
                borderWidth: "2px"
              }}
              value={emailValue}
              onChange={handleEmailValue}
            />
            <br></br>
            <Lock style={{ paddingTop: "15px" }} size="24" color="#029875" />
            <input
              type="password"
              style={{
                marginTop: "10px",
                marginLeft: "10%",
                width: "79%",
                height: "24px",
                borderColor: "#029875",
                borderWidth: "2px"
              }}
              value={passValue}
              onChange={handlePassValue}
            />
            <br></br>
            <input
              style={{
                marginTop: "10%",
                backgroundColor: "#779ecb",
                width: "100%",
                height: "30px",
                color: "white",
                borderWidth: "0px"
              }}
              type="submit"
              value="Submit"
            />
          </form>
        </Row>
      </Container>
    </BackgroundComponent>
  )
}
