<?php

namespace App\enum;

enum EService: string
{
    case ACCOUNT = "Account";
    case ACTION = "Action";
    case CLIENT = "Client";
    case CONTACT = "Contact";
    case CONTACT_LINK = "ContactLink";
    case CONTACT_LINK_TYPE = "ContactLinkType";
    case CONTRAT = "Contrat";
    case CONTRAT_TYPE = "ContratType";
    case FACTURATION = "Facturation";
    case FACTURATION_MODEL = "FacturationModel";
    case FONCTION = "Fonction";
    case INFO = "Info";
    case INFO_TYPE = "InfoType";
    case PRODUCT = "Product";
    case PRODUCT_TYPE = "ProductType";
    case QUANTITY_TYPE = "QuantityType";
    case USER = "User";
}
