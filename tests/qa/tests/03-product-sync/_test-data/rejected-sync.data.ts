import type { RejectedSyncCase } from '../_test-scenarios';

const tooManyAttributeNames = [ 'Color', 'Size', 'Material', 'Style' ];

const tooManyVariationsCount = 100;
const tooManyVariationSizes = Array.from(
	{ length: tooManyVariationsCount },
	( _, i ) => `Size ${ i + 1 }`
);

const tooLongTitle = `POS-647 ${ 'A'.repeat( 250 ) }`; // 258 chars total

export const rejectedSyncCases: RejectedSyncCase[] = [
	{
		title: 'POS-643 | Unsupported product type shows Unsupported status; regression;',
		// Only simple/variable are in the plugin's allowed-product-types list
		// (paypal-pos-sync/services.php) — grouped products fall outside it.
		productData: {
			name: 'POS-643 Grouped Product',
			type: 'grouped',
		},
		expectedStatus: 'unsupported-product-type',
	},
	{
		title: 'POS-644 | Variable product with more than 3 variation attributes is rejected; regression;',
		// Zettle allows at most 3 variant option definitions per product
		// (VariantOptionDefinitionsValidator::MAXIMUM_DEFINITIONS_AMOUNT) — 4 attributes
		// should trip that limit.
		productData: {
			name: 'POS-644 Too Many Attributes',
			type: 'variable',
			attributes: tooManyAttributeNames.map( ( name ) => ( {
				name,
				variation: true,
				visible: true,
				options: [ 'A', 'B' ],
			} ) ),
		},
		expectedStatus: 'too-many-variant-options',
		beforeSync: async ( requestUtils, productId ) => {
			await requestUtils.rest( {
				path: `/wc/v3/products/${ productId }/variations`,
				method: 'POST',
				data: {
					attributes: tooManyAttributeNames.map( ( name ) => ( {
						name,
						option: 'A',
					} ) ),
					regular_price: '10.00',
				},
			} );
		},
	},
	{
		title: 'POS-645 | Variable product with more than 99 variations is rejected; regression;',
		// Zettle allows at most 99 variants per product (ProductValidator::MAXIMUM_VARIANTS_AMOUNT)
		// — 100 variations should trip that limit. Uses the variations batch endpoint to
		// avoid 100 sequential REST round-trips.
		timeout: 10 * 60_000,
		productData: {
			name: 'POS-645 Too Many Variations',
			type: 'variable',
			attributes: [
				{
					name: 'Size',
					variation: true,
					visible: true,
					options: tooManyVariationSizes,
				},
			],
		},
		expectedStatus: 'too-many-variants',
		beforeSync: async ( requestUtils, productId ) => {
			await requestUtils.rest( {
				path: `/wc/v3/products/${ productId }/variations/batch`,
				method: 'POST',
				data: {
					create: tooManyVariationSizes.map( ( size ) => ( {
						attributes: [ { name: 'Size', option: size } ],
						regular_price: '10.00',
					} ) ),
				},
				// Default actionTimeout (30s) isn't enough here: the plugin's lifecycle-event
				// hooks rebuild the full product + variant DTO on every single variation save
				// (see ProductValidator/VariantBuilder), so cost grows with variation count.
				timeout: 5 * 60_000,
			} );
		},
	},
	{
		title: 'POS-647 | Simple product with title exceeding 256 characters is rejected and not synced; regression;',
		productData: {
			name: tooLongTitle,
			regular_price: '10.00',
		},
		// Zettle rejects the create call with a server-side CONSTRAINT_VIOLATION on `name`
		// (size must be 1-256) — the plugin swallows that exception
		// (ExportProductJob::attemptCreate) without persisting a status, so the product
		// resolves to the generic never-synced bucket. See POS-649 for the underlying gap
		// where this specific reason isn't surfaced to the user.
		expectedStatus: 'not-synced',
	},
];
