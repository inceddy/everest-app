# Everest - App Component
The App Component extends the Everest - Container Component with typical providers and methods used by web applications.

## Default providers
### Session
The Session object is used to handle the user session. The session is started the first time this dependency is required.

### Request
Contains the native instance `Everest\Http\Requests\SeverRequest` object created from PHP globals.

### Options
The Options object 