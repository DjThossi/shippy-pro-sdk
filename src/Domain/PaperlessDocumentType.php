<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

enum PaperlessDocumentType: int
{
    case Invoice = 0;
    case AirWaybill = 1;
    case ProformaInvoice = 2;
    case CertificateOfOrigin = 3;
    case UsmcaCertificateOfOrigin = 4;
    case CommercialInvoice = 5;
    case CustomsDeclaration = 6;
    case Other = 99;
}
