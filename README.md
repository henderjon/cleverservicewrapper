# Clever Service Wrapper

Clever.com offers a service whereby they bridge the gap between a school's
student information system (SIS) and any given vendor.

Clever's PHP SDK ([found here](https://github.com/Clever/clever-php)) isn't
the most injectable library you'll find. I wrote this wrapper as a means of
injecting the Clever API into an application.

It includes a retry loop with an exponential backoff (their prescribed
best practice) and a reasonably verbose logger for logging what may/not
go wrong.

The official SDK is only at version 0.3 so keep in mind that until they
tag a stable version, it's wide open to change and breakage.