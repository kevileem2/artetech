import React, { useState, useEffect } from "react"
import {
  Container,
  Row,
  HeaderNav,
  Section,
  SectionRow,
  HeaderTitle,
  HeaderContainer
} from "./components"
import { SignOutAlt } from "styled-icons/fa-solid"
import axios from "axios"

export default () => {
  const [activeIndex, setActiveIndex] = useState(false)

  const renderSection = () => {
    if (activeIndex) {
      return <Statistics />
    } else {
      return <Data />
    }
  }

  const handleFirstSectionPress = () => {
    setActiveIndex(0)
  }

  const handleSecondSectionPress = () => {
    setActiveIndex(1)
  }

  const handleLogOut = () => {
    localStorage.removeItem("authentication")
    window.location.reload()
  }

  return (
    <Container>
      <HeaderNav>
        <HeaderContainer>
          <HeaderTitle>{!activeIndex ? "Home" : "Statistieken"}</HeaderTitle>
          <SignOutAlt
            onClick={handleLogOut}
            style={{ position: "relative", left: "40%" }}
            size="24"
            color="white"
          />
        </HeaderContainer>
      </HeaderNav>
      <SectionRow>
        <Section onClick={handleFirstSectionPress} isActive={!activeIndex}>
          Dag indeling
        </Section>
        <Section onClick={handleSecondSectionPress} isActive={activeIndex}>
          Statistieken
        </Section>
      </SectionRow>
      <Row>{renderSection()}</Row>
    </Container>
  )
}

const Data = () => {
  const [clients, setClients] = useState([])
  const [client, setClient] = useState([])
  const [terms, setTerms] = useState([])
  const [projects, setProjects] = useState([])
  const [startTime, setStartTime] = useState()
  const [endTime, setEndTime] = useState()
  const [activities, setActivities] = useState()
  const [materials, setMaterials] = useState()
  const [transport, setTransport] = useState()

  const fetchClients = () => {
    axios
      .get("http://localhost:3000/clients")
      .then(res => res)
      .then(data => setClients(data.data))
  }
  const fetchTerms = () => {
    axios
      .get("http://localhost:3000/terms")
      .then(res => res)
      .then(data => setTerms(data.data))
  }
  const fetchProjects = () => {
    axios
      .get("http://localhost:3000/projects")
      .then(res => res)
      .then(data => setTerms(data.data))
  }
  useEffect(() => {
    fetchClients()
    setClient(clients[0])
    fetchProjects()
  }, [])

  useEffect(() => {
    projects.forEach(element => {
      if (
        element.employee_id ===
        JSON.parse(localStorage.getItem("authentication")).id
      ) {
        const now = new Date().now()
      }
    })
  }, [])

  const onSubmit = () => {}
  return (
    <>
      <h3 style={{ color: "#029875" }}>Vul gegevens van de dag in!</h3>
      <form style={{ marginTop: "10%" }} onSubmit={onSubmit}>
        <br></br>
        <label style={{ color: "#029875" }}>Start Tijd</label>
        <input
          type="time"
          required
          style={{
            marginTop: "10px",
            width: "100%",
            height: "24px",
            borderColor: "#029875",
            borderWidth: "2px"
          }}
        />
        <br></br>
        <label style={{ color: "#029875" }}>Eind Tijd</label>
        <input
          type="time"
          required
          style={{
            marginTop: "10px",
            width: "100%",
            height: "24px",
            borderColor: "#029875",
            borderWidth: "2px"
          }}
        />
        <br></br>
        <label style={{ color: "#029875" }}>Pauze</label>
        <input
          type="time"
          required
          style={{
            marginTop: "10px",
            width: "100%",
            height: "24px",
            borderColor: "#029875",
            borderWidth: "2px"
          }}
        />
        <br></br>
        <label style={{ color: "#029875" }}>Activiteiten</label>
        <input
          type="text"
          required
          style={{
            marginTop: "10px",
            width: "100%",
            height: "24px",
            borderColor: "#029875",
            borderWidth: "2px"
          }}
        />
        <br></br>
        <label style={{ color: "#029875" }}>Materialen</label>
        <input
          type="text"
          required
          style={{
            marginTop: "10px",
            width: "100%",
            height: "24px",
            borderColor: "#029875",
            borderWidth: "2px"
          }}
        />
        <br></br>
        <label style={{ color: "#029875" }}>Transport (km)</label>
        <input
          type="number"
          required
          style={{
            marginTop: "10px",
            width: "100%",
            height: "24px",
            borderColor: "#029875",
            borderWidth: "2px"
          }}
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
    </>
  )
}

const Statistics = () => {
  return (
    <div>
      <p>statistics</p>
    </div>
  )
}
