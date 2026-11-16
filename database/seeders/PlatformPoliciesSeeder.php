<?php

namespace Database\Seeders;

use App\Models\PlatformPolicy;
use App\Models\PlatformSetting;
use App\Services\ActivityLogger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformPoliciesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::definitions() as $slug => $definition) {
                $policy = PlatformPolicy::where('slug', $slug)->lockForUpdate()->first() ?? new PlatformPolicy(['slug' => $slug]);
                $policy->fill($definition);
                if ($policy->exists && ! $policy->isDirty(['title', 'body'])) {
                    continue;
                }
                $policy->version = max(1, (int) $policy->version + 1);
                $policy->updated_by = null;
                $policy->save();
                ActivityLogger::log('policy.updated', 'Platform Settings', $policy->title.' updated for all account types (v'.$policy->version.').', $policy);
                $this->command?->info($policy->title.' published as version '.$policy->version.'.');
            }
        });
    }

    /** @return array<string, array{title: string, body: string}> */
    public static function definitions(): array
    {
        $support = (string) PlatformSetting::get('support_email', 'support@shopleap.shop');
        $proofRetention = (int) config('delivery_proofs.retention_days', 180);

        return [
            PlatformPolicy::TERMS_SLUG => ['title' => 'Terms and Conditions', 'body' => <<<TEXT
These terms explain the responsibilities of everyone using Shopleap: visitors, buyers, sellers, logistics companies, hub managers, riders, and authorized administrators. They apply to the marketplace, logistics portal, rider app, and related services. Read the Privacy Policy alongside these terms to understand how personal information is handled.

1. Accounts and eligibility
Provide accurate information and keep your contact details current. Use an account only if you can enter the relevant agreement under Philippine law, or have the authorization legally required for your situation. Business and organization accounts must be operated by an authorized representative. Riders must meet applicable age, licensing, vehicle, and road-safety requirements.

Some accounts require email verification, identity or business documents, or approval before particular features become available. Registration or approval does not guarantee sales, assignments, income, delivery capacity, or continuing access. Separate roles remain separate accounts even when they use the same email address.

2. Passwords, access, and account responsibility
Keep passwords, OTPs, invitation links, recovery links, and access tokens private. Do not share an individual login or use another person's identity. Use the available recovery process if access is lost, and report suspected unauthorized access to {$support}. A company must give each hub manager their own authorized account rather than distribute the company's login.

You are responsible for actions you authorize through your account. Contact support promptly about compromised access; responsibility for disputed activity will depend on the circumstances and applicable law.

3. Marketplace transactions and prices
Shopleap provides tools to connect buyers, sellers, and delivery partners, manage orders, and support transactions. Sellers are responsible for their listings and goods; delivery partners are responsible for the services they undertake. Shopleap's own obligations and any mandatory platform responsibilities remain subject to applicable law.

Review the item description, quantity, delivery address, available payment method, shipping fee, and order total before ordering. Prices, availability, promotions, and delivery estimates shown for a future purchase may change. An accepted order must not be silently repriced; errors, unavailable stock, and proposed changes must be handled through the order and support processes, with any refund required by law.

4. Buyers
Provide a complete delivery address and a reachable contact number. Place genuine orders, pay amounts legitimately due, and cooperate with reasonable delivery arrangements. Do not make false delivery, payment, return, or refund claims. Inspect delivered items when reasonably possible and report concerns with the order reference and relevant evidence. Do not publish other people's contact information in reviews or messages.

5. Sellers
Keep business details, pickup addresses, stock, prices, product descriptions, images, and required disclosures accurate. List only goods you may lawfully sell and fulfill orders you accept. Respect intellectual property, product safety, labeling, tax, permit, and consumer-protection requirements that apply to your business. Do not list prohibited, counterfeit, stolen, or misleadingly described goods.

Prepare parcels securely, provide truthful shipment information, and cooperate with delivery, cancellation, return, and complaint processes. Seller fees, commissions, deductions, and settlement details must follow the applicable disclosed terms and transaction records; a displayed report alone does not confirm that a payout has been received.

6. Logistics companies and hub managers
A company is responsible for the branches and manager access it creates or authorizes. Keep branch contacts, map locations, and service coverage accurate. Respect existing barangay ownership and rider assignments; do not claim another hub's coverage or access another company's records.

Hub managers must protect parcels in their custody, use the correct manifests and parcel scans, and record dispatches, receipts, discrepancies, missing parcels, and handovers truthfully. Follow the platform's approval, suspension, coverage, and active-parcel restrictions. Company oversight does not authorize access to a manager's password or to unrelated users' personal information.

7. Riders
Use your own approved rider account and keep your identity, vehicle, license, contact details, and assigned coverage current. Accept only assignments you can safely and lawfully perform. Follow traffic and safety rules, treat users respectfully, protect parcels, and submit accurate pickup and delivery updates and any required proof.

Collect only the cash amount legitimately due for the assigned order. Protect cash in your custody, record collections and handovers accurately, and follow the applicable hub remittance and payout process. Do not mark an order delivered before delivery or reuse misleading proof. Earnings shown as owed are distinct from amounts actually paid. These terms do not determine employment status or waive labor rights that apply by law.

8. Payments, fees, cash, and remittances
Use only payment and payout methods supported or expressly authorized for the transaction. Review displayed charges and retain relevant references. Never send an OTP, password, or full account credential to another user as proof of payment. Report unexpected charges, cash discrepancies, payout disputes, or requests to move a transaction outside the agreed process.

Buyers, sellers, hubs, companies, and riders must distinguish order totals, shipping fees, commissions, rider pay, cash on hand, remittances, and settlements. A pending record, uploaded transfer proof, or account balance does not by itself establish completed payment. Applicable amounts and responsibilities are determined by transaction records, disclosed arrangements, and law.

9. Deliveries, cancellations, returns, and refunds
Delivery availability depends on approved coverage, available partners, and the particular order. Estimated delivery times are estimates. Weather, safety issues, inaccurate addresses, and other disruptions may require updates or support intervention; report delays and losses rather than changing records to hide them.

Use the available order and support processes to request cancellation, return, refund, repair, or replacement. Eligibility and handling depend on the order, evidence, disclosed arrangements, and applicable law. Platform workflow deadlines do not remove statutory rights. Nothing here permits a blanket refusal of remedies for defective, unsafe, misdescribed, or undelivered goods where the law provides a remedy.

10. Acceptable use and respectful conduct
Do not commit fraud, impersonate someone, harass or threaten others, submit harmful content, manipulate reviews or promotions, or falsify documents, parcel scans, delivery evidence, or financial records. Do not bypass access controls, extract private information, disrupt services, or use another account to evade a restriction. Access to an export or report does not permit unrelated reuse or resale of its personal data.

11. Content and intellectual property
Shopleap's branding and software are protected by applicable intellectual-property law. Users retain rights they hold in their own listings, photos, reviews, and other content. By submitting content, you authorize its use as reasonably necessary to display it, operate the relevant service, and handle support or disputes. Submit only content you have the right to provide; this permission does not transfer ownership or authorize unrelated use of private documents.

12. Privacy and authorized administration
Use personal information received through Shopleap only for the relevant transaction, delivery, business administration, support, or other lawful purpose. Limit access to people who need it and report suspected misuse. Administrators must act within their permissions and handle verification, moderation, financial review, and support information confidentially. The Privacy Policy explains collection, sharing, retention, and privacy requests for every account type.

13. Restrictions, suspension, and closure
Shopleap may investigate misuse and restrict access when reasonably needed for security, legal compliance, safety, or a breach of these terms. Where appropriate, reasons and a way to seek review will be provided; urgent protection or legal requirements may require immediate action. Contact support to contest a restriction or request account closure.

Closing or restricting an account does not erase outstanding parcel, payment, cash, settlement, or lawful recordkeeping obligations. Existing orders and funds must be addressed through the applicable processes. Some records may need to be retained as described in the Privacy Policy.

14. Service availability and legal rights
Services may be interrupted for maintenance or events outside reasonable control. No policy promises uninterrupted access, a fixed income, or a guaranteed delivery time. Responsibility for loss or damage depends on the facts and applicable law. These terms do not exclude liability or limit consumer, privacy, labor, or other rights that cannot lawfully be excluded or limited.

15. Changes, governing law, and support
These terms are governed by applicable Philippine law, including consumer and electronic-commerce protections where relevant. You may seek assistance or remedies from the appropriate authority or court; contacting Shopleap first is encouraged but does not remove a legal right to complain.

The version and last-updated date appear on this page. Material changes will be communicated through appropriate available channels, and renewed agreement will be requested where legally required. Changes do not automatically rewrite completed transactions or past acceptance records. For account, order, partner, or policy questions, contact {$support} and include the relevant reference without sending passwords or OTPs.
TEXT],
            PlatformPolicy::PRIVACY_SLUG => ['title' => 'Privacy Policy', 'body' => <<<TEXT
This notice explains how Shopleap handles personal information in the marketplace, logistics portal, rider app, and related support services. It applies to visitors, buyers, sellers, company representatives, hub managers, riders, administrators, and delivery recipients whose details are provided for an order, including recipients without an account.

1. Who to contact
Shopleap handles information needed to operate its platform and coordinate its services. For a privacy question, access or correction request, suspected disclosure, or account-deletion request, contact {$support} and state that it is a privacy request. Businesses and delivery partners may have their own legal responsibilities for the records they control; Shopleap can help identify the party relevant to a particular transaction.

2. Account and verification information
Depending on the role and feature, information may include your name, email, phone number, birth date or age, sex where requested in an application, address, login and verification records, and policy acceptance versions and dates. Seller and logistics applications may include business names, permits, registration records, and identification documents. Rider applications may include a profile photo, vehicle details, plate number, license, and vehicle registration documents.

We receive information you enter, documents you upload, information an authorized company representative provides to create a branch or manager account, and records generated while you use the service. Some identity documents may contain sensitive personal information; access and use must be limited to the lawful verification or compliance purpose.

3. Orders, money, messages, and activity
Transaction records may include products, seller and recipient details, shipping addresses, order totals, promotions, payment references and proofs, refunds, settlements, rider earnings, payouts, cash collections, and remittances. They also include parcel scans, bag manifests, handovers, discrepancies, delivery attempts, and proof of delivery.

We process support requests, user messages, reviews, announcements and read receipts, and records of relevant account and administrative actions. Technical records can include IP addresses, browser or device information, sessions, access tokens, sign-in times, and security or error logs. Do not put passwords, OTPs, full payment credentials, or unnecessary identity documents into a public review or message.

4. Addresses, maps, photos, and location
Saved addresses and hub service areas are used to determine pickup and delivery coverage and rider assignments. A company map displays its hubs' coordinates. A location selected from address dropdowns may produce an approximate barangay pin; it is not proof of a branch's exact position.

Where a delivery feature accepts them, proof photos, timestamps, recipient details, notes, and optional coordinates are used to document and review the delivery. Providing a hub pin or delivery coordinate does not itself authorize continuous background tracking of a rider or recipient. Any device permission requested by a feature should be considered separately.

Map tiles and address-to-location lookup may be supplied by mapping providers. Loading a map can disclose technical request information such as an IP address to the provider, and a lookup can send the selected province, municipality, and barangay. Do not enter private notes or personal identifiers into a location lookup.

5. Why information is used
Information is used to register and verify accounts; authenticate users and recover access; evaluate partner applications; publish shops and listings; process orders and payments; plan coverage; assign pickups and deliveries; track parcels; document delivery; handle cash, payouts, refunds, and settlements; provide support; investigate misuse; and meet lawful obligations. Operational and aggregate reports help authorized users manage their own business and delivery activity.

Personal information must be relevant and proportionate to the purpose. A field or document needed for a particular application may be necessary to provide that feature. If required information is not supplied, the relevant application, order, or service may not proceed; this does not authorize collection of unrelated information.

6. Lawful grounds and your choices
Depending on the information and purpose, processing may be necessary for an agreement or a step you request, a legal obligation, a legitimate interest balanced against your rights, or consent. Sensitive personal information requires a lawful ground appropriate to that category. Accepting the Terms or reading this notice is not blanket consent to every possible use of personal data.

Where consent is the basis for an optional purpose, you may refuse or withdraw it through support or an available setting. Withdrawal does not invalidate earlier lawful processing or require deletion of information still needed on another lawful ground. Essential service communications, fraud prevention, and legally required records may need to continue.

7. Who can see information
Buyers and sellers receive information relevant to their transaction. The assigned hub and rider receive recipient and pickup details needed to fulfill it. A logistics company can oversee its own hubs and manager accounts and view the operational and financial records available to its role. Hub managers handle their own authorized rider, parcel, coverage, and cash records. Authorized administrators access information needed for verification, support, moderation, security, or financial review.

Public shop details, listings, and content intentionally published by a user can be visible to visitors. Verification documents and delivery proofs are intended for authorized operational or review access, not public publication. Each participant must use information received only for an authorized, lawful purpose and must not reuse recipient details for personal contact, unrelated marketing, or resale.

8. Service providers, disclosures, and transfers
Providers supporting hosting, storage, communications, maps, and any payment or payout feature may process the information needed for that service. Information may also be disclosed when legally required, to respond to lawful requests, or where otherwise lawfully necessary to establish or defend a claim or protect people and the service. Shopleap does not offer users' personal information for sale.

Infrastructure or providers may process information outside the Philippines. Where a transfer occurs, applicable data-protection responsibilities continue, and appropriate contractual, organizational, and technical safeguards are required. Contact support for information about the recipients or transfers relevant to your data; this notice does not imply that every provider receives every category of information.

9. Cookies and account security
Cookies and similar storage support functions such as login sessions, request protection, and interface preferences. The rider app also uses access tokens for authenticated requests. Blocking essential storage can prevent login or other features from working. Third-party services may apply their own privacy and storage rules when you load their resources.

Passwords are hashed for authentication, and account permissions and access controls help limit access to personal records. No system can guarantee complete security. Protect your device and credentials, log out on shared devices, and report suspicious access promptly. If a personal-data breach occurs, Shopleap must assess and respond to it and make notifications where required by applicable law.

10. Retention and deletion
Information should be retained only as long as needed for its stated purpose or a lawful recordkeeping requirement. Relevant factors include account activity, unfinished orders, parcel custody, fraud investigations, financial or tax obligations, and unresolved complaints or claims. There is no single retention period for every category.

Delivery-proof media is subject to the configured retention process, currently {$proofRetention} days. Eligible expired media may be removed while associated order, proof metadata, and accounting records remain where still required. This is not a promise that every record is deleted after that period. Account closure, deleting an address, and requesting erasure can have different effects; support can explain what remains and why, including any applicable backup retention.

11. Your privacy rights
Subject to applicable law and any lawful limits, you may seek information about processing, access your personal data, correct inaccurate data, object to processing, withdraw consent where applicable, and request blocking or erasure when the legal grounds exist. Data portability and compensation may also be available in the circumstances provided by law. You may complain to the National Privacy Commission through its official channels at privacy.gov.ph.

Send requests to {$support}, identify the account or transaction concerned, and describe the right you wish to exercise. We may need proportionate identity or authority checks to avoid disclosing data to the wrong person; do not send a password or OTP. A request may be limited where necessary to protect another person's rights or comply with a lawful obligation. Any refusal or limitation should be explained together with available next steps.

12. Recipients, representatives, and younger users
If you provide another person's delivery or business contact details, ensure you are authorized to do so, give them access to this notice, and share only what the transaction needs. Representatives should submit only information relevant to their role. Do not create an account for a child who cannot legally use the service independently; required parental or guardian authorization and any age-related legal protections must be respected. Contact support if a child's information was submitted improperly.

13. Updates and related policies
The Terms and Conditions describe platform responsibilities; this Privacy Policy describes personal-information handling. Both are available publicly and during registration. The version and last-updated date appear on this page. Material changes to purposes or practices will be communicated through appropriate available channels, and further consent will be sought when required. A policy update does not mean that an existing user has accepted a new version or consented to a new purpose.
TEXT],
        ];
    }
}
