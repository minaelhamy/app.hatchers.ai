<?php

use App\Http\Controllers\OsShellController;
use App\Http\Controllers\Integration\IdentitySyncController;
use App\Http\Controllers\Integration\ModuleSnapshotController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OsShellController::class, 'landing'])->name('landing');
Route::get('/plans', [OsShellController::class, 'plans'])->name('plans');
Route::post('/integrations/snapshots/{module}', [ModuleSnapshotController::class, 'store'])->name('integrations.snapshots.store');
Route::post('/integrations/identities/{role}', [IdentitySyncController::class, 'store'])->name('integrations.identities.store');
Route::post('/integrations/stripe/webhook', [OsShellController::class, 'stripeWebhook'])->name('integrations.stripe.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/login', [OsShellController::class, 'login'])->name('login');
    Route::post('/login', [OsShellController::class, 'authenticate'])->name('login.authenticate');
    Route::get('/verify-email', [OsShellController::class, 'verifyEmailNotice'])->name('verification.email.notice');
    Route::post('/verify-email', [OsShellController::class, 'verifyEmail'])->name('verification.email.verify');
    Route::post('/verify-email/resend', [OsShellController::class, 'resendEmailVerification'])->name('verification.email.resend');
    Route::get('/verify-login', [OsShellController::class, 'verifyLoginNotice'])->name('verification.login.notice');
    Route::post('/verify-login', [OsShellController::class, 'verifyLogin'])->name('verification.login.verify');
    Route::post('/verify-login/resend', [OsShellController::class, 'resendLoginVerification'])->name('verification.login.resend');
    Route::get('/onboarding', [OsShellController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [OsShellController::class, 'storeOnboarding'])->name('onboarding.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [OsShellController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/founder', [OsShellController::class, 'dashboard'])->name('dashboard.founder');
    Route::get('/dashboard/mentor', [OsShellController::class, 'dashboard'])->name('dashboard.mentor');
    Route::get('/dashboard/admin', [OsShellController::class, 'dashboard'])->name('dashboard.admin');
    Route::get('/mentor/founders/{founder}', [OsShellController::class, 'mentorFounderDetail'])->name('mentor.founders.show');
    Route::post('/mentor/founders/{founder}/notes', [OsShellController::class, 'mentorSaveFounderNotes'])->name('mentor.founders.notes');
    Route::post('/mentor/founders/{founder}/actions/{actionPlan}/status', [OsShellController::class, 'mentorUpdateFounderActionStatus'])->name('mentor.founders.actions.status');
    Route::get('/activity', [OsShellController::class, 'founderActivity'])->name('founder.activity');
    Route::get('/first-100', [OsShellController::class, 'founderFirstHundred'])->name('founder.first-100');
    Route::get('/pods', [OsShellController::class, 'founderPods'])->name('founder.pods');
    Route::post('/pods/{pod}/join', [OsShellController::class, 'founderJoinPod'])->name('founder.pods.join');
    Route::post('/pods/{pod}/posts', [OsShellController::class, 'founderStorePodPost'])->name('founder.pods.posts.store');
    Route::post('/first-100/leads', [OsShellController::class, 'founderStoreLead'])->name('founder.first-100.leads.store');
    Route::post('/first-100/leads/{lead}', [OsShellController::class, 'founderUpdateLead'])->name('founder.first-100.leads.update');
    Route::post('/first-100/leads/{lead}/touch', [OsShellController::class, 'founderLogLeadTouch'])->name('founder.first-100.leads.touch');
    Route::post('/first-100/acquisition/{leadChannel}/apply', [OsShellController::class, 'founderApplyAcquisitionPlaybook'])->name('founder.first-100.acquisition.apply');
    Route::post('/first-100/promo-links', [OsShellController::class, 'founderStorePromoLink'])->name('founder.first-100.promo-links.store');
    Route::get('/first-100/promo-links/{promoLink}/kit', [OsShellController::class, 'founderPromoLinkKit'])->name('founder.first-100.promo-links.kit');
    Route::get('/first-100/promo-links/{promoLink}/asset/{variant}', [OsShellController::class, 'founderPromoLinkAsset'])->name('founder.first-100.promo-links.asset');
    Route::get('/first-100/promo-links/{promoLink}/asset/{variant}.svg', [OsShellController::class, 'founderPromoLinkAssetSvg'])->name('founder.first-100.promo-links.asset.svg');
    Route::get('/first-100/promo-links/{promoLink}/qr.svg', [OsShellController::class, 'founderPromoLinkQrSvg'])->name('founder.first-100.promo-links.qr-svg');
    Route::get('/first-100/promo-links/{promoLink}/qr.png', [OsShellController::class, 'founderPromoLinkQrPng'])->name('founder.first-100.promo-links.qr-png');
    Route::get('/inbox', [OsShellController::class, 'founderInbox'])->name('founder.inbox');
    Route::get('/notifications', [OsShellController::class, 'founderNotifications'])->name('founder.notifications');
    Route::get('/learning-plan', [OsShellController::class, 'founderLearningPlan'])->name('founder.learning-plan');
    Route::post('/learning-plan/{actionPlan}/status', [OsShellController::class, 'founderUpdateLearningStatus'])->name('founder.learning-plan.status');
    Route::get('/tasks', [OsShellController::class, 'founderTasks'])->name('founder.tasks');
    Route::post('/tasks/{actionPlan}/status', [OsShellController::class, 'founderUpdateTaskStatus'])->name('founder.tasks.status');
    Route::get('/legacy-tools', [OsShellController::class, 'founderLegacyTools'])->name('founder.legacy-tools');
    Route::get('/ai-studio/open', [OsShellController::class, 'founderAtlasWorkspace'])->name('founder.ai-tools.open');
    Route::match(['GET', 'POST'], '/ai-studio/proxy/{proxyPath?}', [OsShellController::class, 'founderAtlasWorkspaceProxy'])
        ->where('proxyPath', '.*')
        ->name('founder.ai-tools.proxy');
    Route::get('/commerce', [OsShellController::class, 'founderCommerce'])->name('founder.commerce');
    Route::get('/commerce/wallet', [OsShellController::class, 'founderWallet'])->name('founder.commerce.wallet');
    Route::get('/commerce/wallet/export', [OsShellController::class, 'founderWalletExport'])->name('founder.commerce.wallet.export');
    Route::get('/commerce/orders', [OsShellController::class, 'founderOrders'])->name('founder.commerce.orders');
    Route::get('/commerce/bookings', [OsShellController::class, 'founderBookings'])->name('founder.commerce.bookings');
    Route::post('/commerce/pricing-recommendations/{pricingRecommendation}/apply', [OsShellController::class, 'founderApplyPricingRecommendation'])->name('founder.commerce.pricing.apply');
    Route::post('/commerce/pricing-recommendations/{pricingRecommendation}/status', [OsShellController::class, 'founderUpdatePricingRecommendationStatus'])->name('founder.commerce.pricing.status');
    Route::post('/commerce/offers/{actionPlan}', [OsShellController::class, 'founderUpdateCommerceOffer'])->name('founder.commerce.offer.update');
    Route::post('/commerce/configs/{actionPlan}', [OsShellController::class, 'founderUpdateCommerceConfig'])->name('founder.commerce.config.update');
    Route::post('/commerce/settings', [OsShellController::class, 'founderSaveCommerceConfig'])->name('founder.commerce.settings.store');
    Route::post('/commerce/settings/toggle', [OsShellController::class, 'founderToggleCommerceConfig'])->name('founder.commerce.settings.toggle');
    Route::post('/commerce/payout-account', [OsShellController::class, 'founderSavePayoutAccount'])->name('founder.commerce.payout-account.store');
    Route::get('/commerce/payout-account/connect', [OsShellController::class, 'founderStartStripePayoutOnboarding'])->name('founder.commerce.payout-account.connect');
    Route::get('/commerce/payout-account/return', [OsShellController::class, 'founderHandleStripePayoutReturn'])->name('founder.commerce.payout-account.return');
    Route::post('/commerce/payout-request', [OsShellController::class, 'founderRequestPayout'])->name('founder.commerce.payout-request.store');
    Route::post('/commerce/orders/update', [OsShellController::class, 'founderUpdateOrderOperation'])->name('founder.commerce.orders.update');
    Route::post('/commerce/orders/refund', [OsShellController::class, 'founderRefundOrder'])->name('founder.commerce.orders.refund');
    Route::post('/commerce/orders/customer', [OsShellController::class, 'founderUpdateOrderCustomer'])->name('founder.commerce.orders.customer');
    Route::post('/commerce/orders/fulfillment', [OsShellController::class, 'founderUpdateOrderFulfillment'])->name('founder.commerce.orders.fulfillment');
    Route::post('/commerce/bookings/update', [OsShellController::class, 'founderUpdateBookingOperation'])->name('founder.commerce.bookings.update');
    Route::post('/commerce/bookings/refund', [OsShellController::class, 'founderRefundBooking'])->name('founder.commerce.bookings.refund');
    Route::post('/commerce/bookings/customer', [OsShellController::class, 'founderUpdateBookingCustomer'])->name('founder.commerce.bookings.customer');
    Route::post('/commerce/bookings/schedule', [OsShellController::class, 'founderUpdateBookingSchedule'])->name('founder.commerce.bookings.schedule');
    Route::get('/settings', [OsShellController::class, 'founderSettings'])->name('founder.settings');
    Route::post('/settings', [OsShellController::class, 'founderUpdateSettings'])->name('founder.settings.update');
    Route::get('/ai-tools', [OsShellController::class, 'founderAiTools'])->name('founder.ai-tools');
    Route::get('/search', [OsShellController::class, 'founderSearch'])->name('founder.search');
    Route::get('/media-library', [OsShellController::class, 'founderMediaLibrary'])->name('founder.media-library');
    Route::get('/analytics', [OsShellController::class, 'founderAnalytics'])->name('founder.analytics');
    Route::get('/coming-soon/{feature}', [OsShellController::class, 'founderComingSoon'])->name('founder.coming-soon');
    Route::get('/automations', [OsShellController::class, 'founderAutomations'])->name('founder.automations');
    Route::post('/automations', [OsShellController::class, 'founderStoreAutomation'])->name('founder.automations.store');
    Route::post('/automations/templates', [OsShellController::class, 'founderStoreAutomationTemplate'])->name('founder.automations.templates.store');
    Route::get('/marketing', [OsShellController::class, 'founderMarketing'])->name('founder.marketing');
    Route::post('/marketing/campaign', [OsShellController::class, 'founderCreateCampaign'])->name('founder.marketing.campaign.create');
    Route::post('/marketing/campaign/archive', [OsShellController::class, 'founderArchiveCampaign'])->name('founder.marketing.campaign.archive');
    Route::post('/marketing/campaign/restore', [OsShellController::class, 'founderRestoreCampaign'])->name('founder.marketing.campaign.restore');
    Route::post('/marketing/campaign/duplicate', [OsShellController::class, 'founderDuplicateCampaign'])->name('founder.marketing.campaign.duplicate');
    Route::post('/marketing/content-request', [OsShellController::class, 'founderCreateContentRequest'])->name('founder.marketing.content-request.create');
    Route::post('/marketing/content-request/status', [OsShellController::class, 'founderUpdateContentRequestStatus'])->name('founder.marketing.content-request.status');
    Route::post('/marketing/content-request/generate', [OsShellController::class, 'founderGenerateContentDraft'])->name('founder.marketing.content-request.generate');
    Route::post('/marketing/content-request/save-draft', [OsShellController::class, 'founderSaveContentDraft'])->name('founder.marketing.content-request.save-draft');
    Route::post('/marketing/content-request/publish', [OsShellController::class, 'founderPublishContentRequest'])->name('founder.marketing.content-request.publish');
    Route::get('/workspace/launch/{module}', [OsShellController::class, 'launchWorkspace'])->name('workspace.launch');
    Route::get('/admin/control', [OsShellController::class, 'adminControl'])->name('admin.control');
    Route::get('/admin/system-access', [OsShellController::class, 'adminSystemAccess'])->name('admin.system-access');
    Route::get('/admin/identity', [OsShellController::class, 'adminIdentity'])->name('admin.identity');
    Route::get('/admin/blueprints', [OsShellController::class, 'adminBlueprints'])->name('admin.blueprints');
    Route::post('/admin/blueprints', [OsShellController::class, 'adminStoreBlueprint'])->name('admin.blueprints.store');
    Route::post('/admin/blueprints/founder-review', [OsShellController::class, 'adminReviewFounderBlueprint'])->name('admin.blueprints.founder-review');
    Route::get('/admin/commerce', [OsShellController::class, 'adminCommerce'])->name('admin.commerce');
    Route::get('/admin/finance', [OsShellController::class, 'adminFinance'])->name('admin.finance');
    Route::get('/admin/finance/export', [OsShellController::class, 'adminFinanceExport'])->name('admin.finance.export');
    Route::post('/admin/finance/adjustment', [OsShellController::class, 'adminFinanceAdjustment'])->name('admin.finance.adjustment');
    Route::post('/admin/finance/checkout/{checkoutSession}/review', [OsShellController::class, 'adminFinanceReviewCheckout'])->name('admin.finance.checkout.review');
    Route::post('/admin/commerce/catalog', [OsShellController::class, 'adminStoreCommerceCatalog'])->name('admin.commerce.catalog.store');
    Route::post('/admin/commerce/catalog/update', [OsShellController::class, 'adminUpdateCommerceCatalog'])->name('admin.commerce.catalog.update');
    Route::post('/admin/commerce/offer/update', [OsShellController::class, 'adminUpdateCommerceOffer'])->name('admin.commerce.offer.update');
    Route::post('/admin/commerce/operation/update', [OsShellController::class, 'adminUpdateCommerceOperation'])->name('admin.commerce.operation.update');
    Route::get('/admin/support', [OsShellController::class, 'adminSupport'])->name('admin.support');
    Route::post('/admin/support/test-mail', [OsShellController::class, 'adminSendSupportTestMail'])->name('admin.support.test-mail');
    Route::post('/admin/support/payouts/{payoutRequest}/approve', [OsShellController::class, 'adminApprovePayoutRequest'])->name('admin.support.payouts.approve');
    Route::post('/admin/support/payouts/{payoutRequest}/reject', [OsShellController::class, 'adminRejectPayoutRequest'])->name('admin.support.payouts.reject');
    Route::post('/admin/identity/backfill', [OsShellController::class, 'adminBackfillIdentity'])->name('admin.identity.backfill');
    Route::get('/admin/modules', [OsShellController::class, 'adminModules'])->name('admin.modules');
    Route::get('/admin/subscribers', [OsShellController::class, 'adminSubscribers'])->name('admin.subscribers');
    Route::post('/admin/control/mentor', [OsShellController::class, 'adminAssignMentor'])->name('admin.control.mentor');
    Route::post('/admin/control/mentor-profile', [OsShellController::class, 'adminUpdateMentorProfile'])->name('admin.control.mentor-profile');
    Route::post('/admin/control/admin-profile', [OsShellController::class, 'adminUpdateAdminProfile'])->name('admin.control.admin-profile');
    Route::post('/admin/control/founder', [OsShellController::class, 'adminUpdateFounder'])->name('admin.control.founder');
    Route::post('/admin/control/subscription', [OsShellController::class, 'adminUpdateSubscription'])->name('admin.control.subscription');
    Route::post('/admin/control/sync', [OsShellController::class, 'adminSyncFounder'])->name('admin.control.sync');
    Route::post('/admin/control/retry-sync', [OsShellController::class, 'adminRetryModuleSync'])->name('admin.control.retry-sync');
    Route::post('/admin/control/exceptions/{exception}/resolve', [OsShellController::class, 'adminResolveException'])->name('admin.control.exceptions.resolve');
    Route::get('/mentor/legacy-tools', [OsShellController::class, 'mentorLegacyTools'])->name('mentor.legacy-tools');
    Route::get('/website', [OsShellController::class, 'website'])->name('website');
    Route::post('/website/build', [OsShellController::class, 'storeWebsiteBuildRequest'])->name('website.build.store');
    Route::post('/website/generate', [OsShellController::class, 'generateWebsiteDraft'])->name('website.generate');
    Route::post('/website/launch-system/apply', [OsShellController::class, 'founderApplyLaunchSystem'])->name('website.launch-system.apply');
    Route::post('/website/draft/regenerate-block', [OsShellController::class, 'founderRegenerateWebsiteDraftBlock'])->name('website.draft.regenerate-block');
    Route::post('/website/setup', [OsShellController::class, 'updateWebsite'])->name('website.setup');
    Route::post('/website/publish', [OsShellController::class, 'publishWebsite'])->name('website.publish');
    Route::post('/website/starter', [OsShellController::class, 'createWebsiteStarter'])->name('website.starter');
    Route::post('/website/domain', [OsShellController::class, 'connectWebsiteDomain'])->name('website.domain');
    Route::post('/assistant/chat', [OsShellController::class, 'assistantChat'])->name('assistant.chat');
    Route::post('/assistant/chat/onboarding-complete', [OsShellController::class, 'completeChatOnboarding'])->name('assistant.chat.onboarding-complete');
    Route::post('/assistant/chat/thread', [OsShellController::class, 'assistantThread'])->name('assistant.chat.thread');
    Route::post('/assistant/chat/reset', [OsShellController::class, 'assistantReset'])->name('assistant.chat.reset');
    Route::post('/assistant/chat/tasks', [OsShellController::class, 'assistantCreateTasks'])->name('assistant.chat.tasks');
    Route::post('/logout', [OsShellController::class, 'logout'])->name('logout');
});

Route::post('/{websitePath}/request-order', [OsShellController::class, 'publicWebsiteOrderRequest'])
    ->where('websitePath', '[A-Za-z0-9\-/]+')
    ->name('public.website.order');
Route::post('/{websitePath}/request-intro', [OsShellController::class, 'publicWebsiteIntroRequest'])
    ->where('websitePath', '[A-Za-z0-9\-/]+')
    ->name('public.website.intro');
Route::post('/{websitePath}/request-booking', [OsShellController::class, 'publicWebsiteBookingRequest'])
    ->where('websitePath', '[A-Za-z0-9\-/]+')
    ->name('public.website.booking');
Route::get('/checkout/public/{websitePath}/success', [OsShellController::class, 'publicCheckoutSuccess'])
    ->where('websitePath', '[A-Za-z0-9\-/]+')
    ->name('public.checkout.success');
Route::get('/checkout/public/{websitePath}/cancel', [OsShellController::class, 'publicCheckoutCancel'])
    ->where('websitePath', '[A-Za-z0-9\-/]+')
    ->name('public.checkout.cancel');
Route::any('/{websiteRoot}/{proxyPath}', [OsShellController::class, 'publicWebsiteProxy'])
    ->where('websiteRoot', '[A-Za-z0-9\-]+')
    ->where('proxyPath', '.*')
    ->name('public.website.proxy');
Route::match(['GET', 'POST'], '/{websitePath}', [OsShellController::class, 'publicWebsite'])
    ->where('websitePath', '[A-Za-z0-9\-/]+')
    ->name('public.website');
