# Resolver
To look-up the services associated to a Business ID the resolver recursively contacts first the [Root Business Service Provider](https://github.com/OpenDiscoveryBiz/root-provider), then the relevant authoritative National og Regional Provider (e.g. the [Danish Business Service Provider](https://github.com/OpenDiscoveryBiz/dk-provider)) and finally one or more chained Business Service Providers chosen by the business entity itself. The results of a look-up query are cached for a period stated for each service.

Typical users of the resolver will be the [Business Investigator](https://github.com/OpenDiscoveryBiz/investigator-client), search engines, and applications (e.g. [Personal Information Managers](https://secure.edps.europa.eu/EDPSWEB/webdav/site/mySite/shared/Documents/EDPS/PressNews/Press/2016/EDPS-2016-16-PIMS_EN.pdf)) that aggregate and prioritize or select service offerings on behalf of businesses or consumers requesting services.

When ready for beta-use, an implementation of the resolver will be made freely available as a service at [OpenDiscovery.biz](https://www.opendiscovery.biz).

The Resolver is one of several components needed to enable Distributed Business Service Discovery scenarios.

These components are currently under initial development and we welcome collaboration on the further development of scope and principles (Contact: [Henrik Biering](mailto:hb@peercraft.com)) as well as the technical implementation (Contact: [Casper Biering](mailto:cb@peercraft.com)).
