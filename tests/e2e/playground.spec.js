/* eslint-disable jest/no-done-callback */
const { test, expect } = require('@playwright/test');

const DOI_SAMPLE = '10.1038/s41586-020-2649-2';

function getPluginRow(page) {
	return page.locator(
		'tr[data-plugin="borges-bibliography-builder/bibliography-builder.php"]:not(.plugin-update-tr)'
	);
}

async function ensurePluginActivated(page) {
	await page.goto('/wp-admin/plugins.php');
	await expect(
		page.getByRole('heading', { level: 1, name: 'Plugins' })
	).toBeVisible();

	const pluginRow = getPluginRow(page);

	await expect(pluginRow).toHaveCount(1);
	await expect(pluginRow).toBeVisible();

	const activateLink = pluginRow.getByRole('link', { name: /^Activate$/i });

	if (await activateLink.count()) {
		await activateLink.click();
		await page.waitForLoadState('networkidle');
	}

	await expect(pluginRow).toContainText('Bibliography');
	await expect(
		pluginRow.getByRole('link', { name: /Activate|Deactivate/i }).first()
	).toBeVisible();
}

test('plugin is active in WordPress Playground', async ({ page }) => {
	await ensurePluginActivated(page);

	await expect(getPluginRow(page)).toBeVisible();
});

async function dismissEditorOverlay(page) {
	for (let attempt = 0; attempt < 3; attempt += 1) {
		const dialog = page.getByRole('dialog').first();
		const dialogCloseButton = dialog
			.getByRole('button', {
				name: /Close|Dismiss|Got it|Okay|OK|Done|Skip/i,
			})
			.first();

		if (
			(await dialog.isVisible().catch(() => false)) &&
			(await dialogCloseButton.isVisible().catch(() => false))
		) {
			await dialogCloseButton.click({ force: true });
			await dialog
				.waitFor({ state: 'hidden', timeout: 5000 })
				.catch(() => {});
		}

		await page.keyboard.press('Escape').catch(() => {});

		const overlay = page.locator('.components-modal__screen-overlay');
		if (!(await overlay.count())) {
			return;
		}

		await overlay
			.first()
			.waitFor({ state: 'hidden', timeout: 2000 })
			.catch(() => {});
	}
}

async function openInserterAndSearch(page, query) {
	await page
		.getByRole('button', { name: /Block Inserter|Toggle block inserter/i })
		.click({ force: true });

	const inserterSearch = page
		.locator(
			'input[placeholder*="Search" i], input[aria-label*="Search" i], [role="searchbox"], .block-editor-inserter__search input'
		)
		.first();

	if (
		!(await inserterSearch.isVisible({ timeout: 3000 }).catch(() => false))
	) {
		const browseAllButton = page
			.getByRole('button', {
				name: /Browse all|See all|Open block inserter/i,
			})
			.first();

		if (await browseAllButton.isVisible().catch(() => false)) {
			await browseAllButton.click({ force: true });
		}
	}

	await expect(inserterSearch).toBeVisible();
	await inserterSearch.fill(query);
}

async function getEditorFrame(page) {
	const editorIframe = page.frameLocator('iframe[name="editor-canvas"]');
	const iframeBody = editorIframe.locator('body');

	if (await iframeBody.isVisible({ timeout: 3000 }).catch(() => false)) {
		return editorIframe;
	}

	return page;
}

async function insertBibliographyBlock(page) {
	await page.waitForFunction(
		() =>
			window.wp?.blocks?.getBlockType(
				'bibliography-builder/bibliography'
			) &&
			window.wp?.blocks?.createBlock &&
			window.wp?.data?.dispatch('core/block-editor')?.insertBlock,
		null,
		{ timeout: 20_000 }
	);

	await page.evaluate(() => {
		const block = window.wp.blocks.createBlock(
			'bibliography-builder/bibliography'
		);
		const editor = window.wp.data.dispatch('core/block-editor');
		editor.insertBlock(block);
		editor.selectBlock(block.clientId);
	});

	const editorFrame = await getEditorFrame(page);
	await expect(
		editorFrame
			.locator('.wp-block-bibliography-builder-bibliography')
			.first()
	).toBeVisible({ timeout: 30_000 });

	return editorFrame;
}

test('bibliography block is discoverable in the editor inserter', async ({
	page,
}) => {
	await ensurePluginActivated(page);
	await page.goto('/wp-admin/post-new.php');
	await page.waitForLoadState('domcontentloaded');

	await dismissEditorOverlay(page);

	await expect(
		page.getByRole('button', {
			name: /Block Inserter|Toggle block inserter/i,
		})
	).toBeVisible({ timeout: 20_000 });
	await openInserterAndSearch(page, 'Bibliography');
	await expect(page.getByText('Bibliography').first()).toBeVisible();
});

test('bibliography block imports a DOI in the Playground editor', async ({
	page,
}) => {
	test.setTimeout(90_000);

	await ensurePluginActivated(page);
	await page.goto('/wp-admin/post-new.php');
	await page.waitForLoadState('domcontentloaded');
	await dismissEditorOverlay(page);

	const editorFrame = await insertBibliographyBlock(page);
	const textarea = editorFrame
		.locator('#bibliography-builder-paste-input')
		.first();
	await expect(textarea).toBeVisible({ timeout: 20_000 });
	await textarea.fill(DOI_SAMPLE);

	await editorFrame.getByRole('button', { name: /^Add$/i }).click();

	const entryText = editorFrame
		.locator('.bibliography-builder-entry-text')
		.first();
	await expect(entryText).toBeVisible({ timeout: 45_000 });
	await expect
		.poll(async () => (await entryText.textContent())?.trim() || '', {
			timeout: 45_000,
		})
		.not.toBe('');
});
