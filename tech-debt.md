* tech-debt-ception: I did not note all the tech debt here! ~~cjw
* I haven't been updating the CHANGELOG since I forked this from the A6A project. ~~cjw
* I am not so good at writing phpspec specs. They are sparse. ~~cjw
* I added in an SSRV token to the login session. Same Site Request Validation token cf. cross site request forgery token. I didn't take time to add any tests. It's included on form submissions to make sure the session generated the form submitted by the session. ~~cjw
* exceptions for violations of the indieauth spec have been permitted and left the code just a little fuzzy. IndieAuth\Validation::authenticationRequest takes a part of the Config as a parameter, but the entire Config is already local to IndieAuth\Validation. It shouldn't be in the constructor as only the one part is meant to be used and only in the authenticationRequest method. I let the spec exceptions through and now this is the result. It continues into Validation\URL. Volkswagen. ~~cjw
