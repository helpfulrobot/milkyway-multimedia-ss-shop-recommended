Silverstripe Shop - Recommended Products
======
**Silverstripe Shop - Recommended Products** adds recommended products functionality to Silverstripe Shop.

Options for setting up recommended products include:
- None: Do not display any recommended products
- Main Category(ies): Pull products from this product's main category(ies)
- Selected Category(ies): Pull products from selected category(ies)
- Selected Product(s): Select specific products to iterate through the recommended items

Configuration Settings (you set this per Buyable Class)

```

Product:
  recommended_limit: 4
  recommended_title: 'Recommended Products'

```

## Install
Add the following to your composer.json file

```

    "require"          : {
		"milkyway-multimedia/ss-shop-recommended": "dev-master"
	}

```

## License
* MIT

## Version
* Version 0.1 - Alpha

## Contact
#### Milkyway Multimedia
* Homepage: http://milkywaymultimedia.com.au
* E-mail: mell@milkywaymultimedia.com.au
* Twitter: [@mwmdesign](https://twitter.com/mwmdesign "mwmdesign on twitter")