import React, {useState} from 'react';
import styled from "styled-components";

export default function (props) {

    const [token, setToken] = useState('');
    const [error, setError] = useState(null);
    const [user, setUser] = useState(props.userData?.username);
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');


    const handleSubmit = async (e) => {
        e.preventDefault()
        const data = Object.fromEntries(new FormData(e.target))

        const response = await fetch('/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: data.username,
                password: data.password
            })
        })

        if (!response.ok) {
            const errorRes = await response.json()
            if (errorRes.error) {
                setError(errorRes.error)
            }
        } else {
            const resData = response.headers.get('Location')
            const userResponse = await fetch(resData, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            const userData = await userResponse.json()
            setUser(userData.username)
        }
    }

    const handleTryUsername = (e) => {
        e.preventDefault;
        setUsername('test@test.fr')
    }

    const handleTryPassword = (e) => {
        e.preventDefault()
        setPassword('pass')
    }

    const handleLogout = async (e) => {
        e.preventDefault()
        const response = await fetch('/logout')
        if (response.ok) {
            setUser(null)
        }

    }

    const handleLoginToken = async (e) => {
        e.preventDefault()
        const response = await fetch('/', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        })
    }

    const handleChangeToken = (e) => {
        setToken(e.target.value)
    }

    const handleTryToken = (e) => {
        e.preventDefault()
        setToken('tcp_42-a45dab994b.251651ec82ed5144bd2e')
    }

    return (
        <Wrapper>
            <div>
                <div>
                    <h1>Login</h1>
                    <Form onSubmit={handleSubmit} method={'post'}>
                        {error && (
                            <Error>
                                <p>{error}</p>
                            </Error>
                        )}
                        <div>
                            <label htmlFor="username">Username</label>
                            <input value={username} onChange={(e) => setUsername(e.target.value)} name={'username'} type="text" id={'username'}/>
                            <Try type={'button'} onClick={handleTryUsername}>test@test.fr</Try>
                        </div>
                        <div>
                            <label htmlFor="username">Password</label>
                            <input value={password} onChange={(e) => setPassword(e.target.value)} name={'password'} type="text" id={'password'}/>
                            <Try type={'button'} onClick={handleTryPassword}>password</Try>
                        </div>
                        <button type={'submit'}>Login</button>
                    </Form>
                    <form onSubmit={handleLoginToken}>
                        <input value={token} onChange={handleChangeToken} type={'text'}/>
                        <Try type={'button'} onClick={handleTryToken}>Put token</Try>
                        <button type={'submit'}>Log with token</button>
                    </form>
                </div>
                <WrapperLog>
                    {user
                        ? (<p>You're logged as {user}</p>)
                        : (<p>Not logged</p>)
                    }
                    <button onClick={handleLogout}>Logout</button>
                </WrapperLog>
            </div>
        </Wrapper>
    )
}

const Wrapper = styled.div`
    min-height: 100vh;
    display: grid;
    place-content: center;
    
  
    & > div {
      display: flex;
      border: 1px solid black;
      border-radius: 1rem;
      padding: 2rem;
    }
  
`

const Try = styled.button`
  border: none;
  background-color: transparent;
  cursor: pointer;
`

const WrapperLog = styled.div`
  padding: 2rem;
  display: grid;
  place-content: center;
`

const Form = styled.form`
    display: grid;
    gap: 1rem;
    max-width: 30rem;
    
  & div {
    display: grid;
  }
  
  & button {
    justify-self: start;
  }
`

const Error = styled.div`
    border: 1px solid darkred;
    background-color: indianred;
    padding-inline: 1rem;
    border-radius: 1rem;
`