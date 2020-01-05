import React from "react"
import Login from "./components/Login"
import Home from "./components/Home"

const MainApp = () => {
  if (localStorage.getItem("authentication")) {
    return <Home />
  } else {
    return <Login />
  }
}

export default MainApp
