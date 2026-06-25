<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportDanbooruTagsCsv extends Command
{
    protected $signature = 'danbooru:import-csv
                            {--section=  : Import only a specific section (character|pose|outfit|scene)}
                            {--group=    : Import only a specific subsection slug}
                            {--fresh     : Delete existing tags in scope before importing}
                            {--min-count=100 : Minimum post count to include a tag}
                            {--file=     : Path to a local CSV file (skips download)}
                            {--url=      : Custom CSV URL (overrides the built-in default)}';

    protected $description = 'Import Danbooru tags from community CSV export (fast, no rate limiting)';

    const DEFAULT_CSV_URL = 'https://raw.githubusercontent.com/DraconicDragon/dbr-e621-lists-archive/main/tag-lists/danbooru/danbooru_2026-04-01_pt20-ia-dd.csv';

    /**
     * Subsection map.
     *
     * Processing order matters: exact matches are resolved first, then regex.
     * When a tag matches multiple subsections, the FIRST match wins.
     *
     * CSV format (no header): name,category,post_count,aliases
     * We only load category=0 (General) tags.
     *
     * 'exact'   → match tag name exactly
     * 'regex'   → array of PHP regex patterns applied to tag name
     * 'exclude' → skip tag even if matched by regex
     * 'nsfw'    → marks all tags in this subsection as NSFW
     */
    private array $map = [

        // ════════════════════════════════════════════════════════════════
        // SECTION: character
        // ════════════════════════════════════════════════════════════════

        'character' => [

            // Hair length must come BEFORE hair_color so short_hair/long_hair
            // are not captured by the /_hair$/ regex in hair_color.
            'hair_length' => [
                'exact' => [
                    'short_hair', 'medium_hair', 'long_hair', 'very_long_hair',
                    'absurdly_long_hair', 'shoulder-length_hair', 'neck-length_hair',
                ],
                'nsfw' => false,
            ],

            'hair_style' => [
                'exact' => [
                    'twintails', 'low_twintails', 'high_twintails', 'side_ponytail',
                    'low_ponytail', 'high_ponytail', 'ponytail', 'braid', 'french_braid',
                    'single_braid', 'twin_braids', 'side_braid', 'crown_braid', 'fishtail_braid',
                    'ahoge', 'huge_ahoge', 'drill_hair', 'twin_drills', 'ringlets', 'hime_cut',
                    'bangs', 'blunt_bangs', 'swept_bangs', 'parted_bangs', 'crossed_bangs',
                    'asymmetrical_bangs', 'sidelocks', 'two_side_up', 'hair_bun', 'double_bun',
                    'single_hair_bun', 'cone_hair_bun', 'folded_ponytail', 'odango',
                    'messy_hair', 'wavy_hair', 'curly_hair', 'straight_hair', 'spiky_hair',
                    'bob_cut', 'pixie_cut', 'undercut', 'mohawk', 'dreadlocks',
                    'hair_rings', 'hair_up', 'hair_over_one_eye', 'hair_between_eyes',
                    'front_ponytail', 'low_twin_braids', 'hair_spread_out', 'hair_intakes',
                    'hair_over_shoulder', 'hair_censor', 'spiked_hair', 'twin_hair_buns',
                ],
                'nsfw' => false,
            ],

            'hair_color' => [
                // Catches: blue_hair, red_hair, multicolored_hair, gradient_hair, etc.
                // hair_length and hair_style exact tags are already assigned above.
                'regex'   => ['/_hair$/'],
                'exclude' => [
                    'hair', 'hair_ornament', 'hair_ribbon', 'hair_bow', 'hair_bun',
                    'hair_flower', 'hair_tie', 'hair_tubes', 'hair_up', 'hair_down',
                    'hair_spread_out', 'hair_censor', 'hair_over_breasts',
                    'hair_over_shoulder', 'dyed_hair', 'natural_hair', 'twin_hair_buns',
                    'hair_between_eyes', 'hair_over_one_eye', 'hair_rings', 'hair_intakes',
                ],
                'nsfw' => false,
            ],

            'eyes' => [
                'exact' => [
                    'heterochromia', 'multicolored_eyes', 'glowing_eyes', 'empty_eyes',
                    'aqua_eyes', 'amber_eyes', 'tareme', 'tsurime', 'sanpaku',
                    'half-closed_eyes', 'wide_eyes', 'wink', 'one_eye_closed',
                    'heart-shaped_eyes', 'star-shaped_pupils', 'slit_pupils', 'no_pupils',
                    'ringed_eyes', 'sparkling_eyes', 'tears', 'watery_eyes',
                    'dull_eyes', 'light_purple_eyes', 'dark_blue_eyes',
                    'asymmetrical_eyes', 'uneven_eyes', 'eye_focus',
                ],
                // Also catches: blue_eyes, red_eyes, closed_eyes, etc.
                'regex'   => ['/_eyes$/'],
                'exclude' => ['eyes', 'eye', 'eyeball', 'covering_eyes', 'peeking_eyes'],
                'nsfw' => false,
            ],

            'face' => [
                'exact' => [
                    'smile', 'grin', 'smirk', 'laughing', 'giggling', 'happy',
                    'blush', 'embarrassed', 'shy', 'nervous', 'surprised', 'shocked',
                    'open_mouth', 'closed_mouth', 'pout', 'angry', 'annoyed',
                    ':>', ':3', ':o', ';)', ':d', ':p', 'xd', '^_^', '>_<',
                    'sad', 'lonely', 'scared', 'fearful',
                    'confused', 'thinking', 'serious', 'stoic', 'expressionless',
                    'blank_stare', 'seductive_smile', 'sultry', 'tongue_out', 'licking_lips',
                    'freckles', 'mole', 'mole_under_eye', 'mole_on_cheek', 'dimples',
                    'fangs', 'sharp_teeth', 'buck_teeth',
                    'nose_blush', 'flushed', 'pale', 'blush_stickers',
                    'lipstick', 'eyeshadow', 'eyeliner', 'mascara',
                    'nose_ring', 'facial_mark', 'facial_hair', 'beard', 'mustache',
                    'frown', 'nosebleed', 'crying', 'ahegao',
                ],
                'nsfw' => false,
            ],

            'ears' => [
                // Catches: cat_ears, dog_ears, fox_ears, elf_ears, etc.
                'regex'   => ['/_ears$/'],
                'exact'   => [
                    'animal_ears', 'kemonomimi_mode', 'pointy_ears', 'large_ears',
                    'floppy_ears', 'ear_piercing', 'ear_blush', 'ear_licking',
                    'detached_animal_ears',
                ],
                'nsfw' => false,
            ],

            'tail' => [
                // Catches: cat_tail, fox_tail, dog_tail, dragon_tail, etc.
                'regex'   => ['/_tail$/'],
                'exact'   => ['multiple_tails', 'tail', 'animal_tail'],
                'nsfw' => false,
            ],

            'body' => [
                'exact' => [
                    'slim', 'skinny', 'petite', 'curvy', 'plump', 'chubby', 'fat',
                    'muscular', 'muscular_female', 'toned', 'abs', 'broad_shoulders',
                    'narrow_waist', 'wide_hips', 'thick_thighs', 'long_legs',
                    '1girl', '1boy', '2girls', '2boys', '3girls', '3boys',
                    'multiple_girls', 'multiple_boys', 'solo', 'solo_focus',
                    'mature_female', 'older_female', 'younger_female',
                    'milf', 'loli', 'shota', 'young', 'adult',
                    'navel', 'belly', 'stomach', 'waist', 'hip', 'back',
                    'armpit', 'collarbone', 'shoulder', 'neck',
                    'thigh', 'knee', 'calf', 'ankle', 'arm', 'elbow', 'wrist',
                    'angel_wings', 'demon_wings', 'dragon_wings', 'fairy_wings',
                    'bird_wings', 'bat_wings', 'feathered_wings', 'single_wing',
                    'horns', 'demon_horns', 'dragon_horns', 'oni_horns', 'single_horn',
                    'halo', 'third_eye', 'cyclops', 'aura', 'glowing', 'scales',
                    'scar', 'tattoo', 'body_markings', 'birthmark',
                    'pointy_ears', 'elf',
                ],
                'nsfw' => false,
            ],

            'skin_color' => [
                'regex'   => ['/_skin$/'],
                'exact'   => [
                    'dark_skin', 'dark-skinned_female', 'dark-skinned_male',
                    'pale_skin', 'tan', 'tanned', 'light_skin', 'olive_skin',
                    'fair_skin', 'white_skin',
                ],
                'nsfw' => false,
            ],

            'breasts' => [
                // Catches: large_breasts, huge_breasts, bouncing_breasts, hanging_breasts,
                // saggy_breasts, flat_breasts, bursting_breasts, etc.
                'regex'   => ['/_breasts$/', '/^breast_/'],
                'exclude' => ['breast_pocket', 'breast_plate', 'breast_armor', 'breast_cancer'],
                'exact'   => [
                    'flat_chest', 'cleavage', 'deep_cleavage', 'sideboob', 'underboob',
                    'topless', 'naked_breasts', 'nipples', 'areolae', 'erect_nipples',
                    'big_areolae', 'puffy_nipples', 'inverted_nipples', 'dark_areolae',
                    'breast_focus', 'between_breasts', 'arm_under_breasts',
                ],
                'nsfw' => true,
            ],

            'ass' => [
                // Catches: huge_ass, ass_focus, grabbing_another's_ass, etc.
                'regex'   => ['/_ass$/', '/^ass_/'],
                'exact'   => [
                    'ass', 'butt', 'buttocks',
                    'butt_crack', 'ass_cleavage', 'pantylines', 'cameltoe',
                    'presenting_ass', 'spreading', 'anal', 'butt_plug', 'tail_plug',
                    'taut_pants', 'taut_shorts', 'taut_skirt', 'taut_leggings',
                ],
                'nsfw' => true,
            ],

            'pussy' => [
                'exact' => [
                    'pussy', 'vagina', 'spread_pussy', 'wet_pussy', 'dripping',
                    'pubic_hair', 'shaved_pussy', 'hairless_pussy', 'censored',
                    'uncensored', 'pussy_juice', 'squirting', 'female_ejaculation',
                    'fingering', 'pussy_licking', 'dildo', 'vibrator', 'sex_toy',
                    'object_insertion', 'vaginal_object_insertion',
                    'penis_in_pussy', 'vaginal', 'creampie',
                ],
                'nsfw' => true,
            ],

            'hands_gestures' => [
                'exact' => [
                    'hand_on_hip', 'hands_on_hips', 'hand_on_own_chest',
                    'hand_on_own_face', 'hand_to_own_mouth', 'hands_clasped',
                    'hands_together', 'praying', 'reaching_out', 'outstretched_arms',
                    'outstretched_hand', 'beckoning', 'peace_sign', 'v', 'thumbs_up',
                    'pointing', 'finger_gun', 'clenched_hands', 'fist',
                    'open_hands', 'arms_up', 'arms_behind_back', 'arms_behind_head',
                    'arms_crossed', 'arms_at_sides', 'hand_between_legs',
                    'hands_in_pockets', 'waving', 'heart_hands', 'pinching',
                    'holding', 'holding_weapon', 'holding_food', 'holding_cup',
                    'grabbing', 'groping',
                ],
                'nsfw' => false,
            ],

            'feet' => [
                'exact' => [
                    'barefoot', 'feet', 'foot', 'toes', 'sole', 'heel',
                    'paw', 'paws', 'animal_feet', 'no_shoes',
                    'toe_ring', 'anklet', 'ankle_ribbon',
                    'footjob', 'foot_licking', 'foot_worship',
                ],
                'nsfw' => true,
            ],

        ],

        // ════════════════════════════════════════════════════════════════
        // SECTION: pose
        // ════════════════════════════════════════════════════════════════

        'pose' => [

            'standing' => [
                'exact' => [
                    'standing', 'contrapposto', 'weight_shift', 'walking', 'running',
                    'jumping', 'floating', 'flying', 'hovering', 'on_tiptoes',
                    'stomping', 'lunging', 'leaning_forward', 'leaning_back',
                    'leaning_to_the_side', 'leaning_on_object', 'back-to-back',
                    'marching', 'tiptoeing', 'crouching', 'squatting', 'kneeling',
                    'on_one_knee', 'warrior_pose',
                ],
                'nsfw' => false,
            ],

            'sitting' => [
                'exact' => [
                    'sitting', 'seiza', 'wariza', 'indian_style', 'cross-legged',
                    'hugging_knees', 'sitting_on_ground', 'sitting_on_bench',
                    'sitting_on_table', 'sitting_on_chair', 'sitting_on_person',
                    'perching', 'lotus_position', 'tailor_sitting',
                ],
                'nsfw' => false,
            ],

            'lying' => [
                'exact' => [
                    'lying', 'on_back', 'on_stomach', 'on_side', 'fetal_position',
                    'sleeping', 'reclining', 'spread_eagle', 'arched_back',
                    'all_fours', 'bent_over', 'prone_bone', 'supine',
                    'face_up', 'face_down',
                ],
                'nsfw' => true,
            ],

            'leg_position' => [
                'exact' => [
                    'legs_up', 'legs_together', 'legs_apart', 'spread_legs',
                    'crossed_legs', 'knees_together', 'knees_up', 'knee_up',
                    'leg_lift', 'standing_split', 'leg_lock', 'thigh_squeeze',
                    'feet_up', 'feet_together', 'pigeon_toed',
                    'figure_four_sitting', 'open_legs',
                ],
                'nsfw' => true,
            ],

            'camera_angle' => [
                'exact' => [
                    'pov', 'from_above', 'from_below', 'from_behind', 'from_side',
                    'close-up', 'portrait', 'full_body', 'upper_body', 'lower_body',
                    'cowboy_shot', 'dutch_angle', 'fisheye', 'wide_shot',
                    'face_only', 'bust_shot', 'thigh_shot', 'silhouette',
                    'out_of_frame', 'feet_out_of_frame', 'head_out_of_frame',
                    'looking_at_viewer', 'looking_away', 'looking_back',
                    'looking_up', 'looking_down', 'looking_to_the_side',
                    'looking_over_shoulder', 'facing_viewer', 'profile',
                ],
                'nsfw' => false,
            ],

            'sex_acts' => [
                // Catches: cum_in_pussy, cum_on_breasts, blowjob, handjob, vaginal_penetration, etc.
                'regex'   => ['/^cum_/', '/_penetration$/'],
                'exclude' => ['breast_pocket', 'breast_plate', 'breast_armor'],
                'exact'   => [
                    'sex', 'vaginal', 'anal', 'oral', 'fellatio', 'cunnilingus',
                    '69', 'handjob', 'paizuri', 'footjob', 'armpit_sex', 'frottage',
                    'tribadism', 'masturbation', 'fingering', 'penetration',
                    'double_penetration', 'triple_penetration', 'gangbang', 'group_sex',
                    'threesome', 'foursome', 'orgy', 'creampie', 'cum_inside',
                    'ejaculation', 'cumshot', 'facial', 'swallowing', 'snowball',
                    'pegging', 'deepthroat', 'irrumatio', 'blowjob',
                    'self_fellatio', 'autofellatio', 'intercrural_sex',
                    'breast_grab', 'breast_press', 'breast_squeeze', 'breast_hold',
                    'breast_lift', 'bouncing_breasts', 'breast_expansion',
                    'presenting', 'pov_hands', 'paizuri_under_clothes',
                    'mating_press', 'doggy_style',
                ],
                'nsfw' => true,
            ],

            'sexual_positions' => [
                'exact' => [
                    'missionary', 'cowgirl_position', 'reverse_cowgirl_position',
                    'standing_sex', 'mating_press', 'spooning', 'amazon_position',
                    'pile_driver', 'side_sex', 'face_sitting', 'sitting_sex',
                    'wall_sex', 'desk_sex', 'shower_sex', 'standing_doggy_style',
                    'standing_missionary', 'lap_dance', 'riding',
                ],
                'nsfw' => true,
            ],

            'bdsm' => [
                'exact' => [
                    'bondage', 'restrained', 'tied_up', 'rope_bondage', 'shibari',
                    'handcuffed', 'cuffed', 'chained', 'blindfolded', 'gagged',
                    'ballgag', 'collar_and_leash', 'leash', 'collar',
                    'spanking', 'whipping', 'paddling', 'flogging',
                    'slave', 'pet_play', 'pony_play',
                    'dominatrix', 'submissive', 'dominant',
                    'orgasm_denial', 'forced_orgasm',
                    'vibrator_under_clothes', 'public_use', 'objectification',
                    'sex_machine', 'milking_machine',
                ],
                'nsfw' => true,
            ],

        ],

        // ════════════════════════════════════════════════════════════════
        // SECTION: outfit
        // ════════════════════════════════════════════════════════════════

        'outfit' => [

            'top' => [
                'exact' => [
                    'shirt', 't-shirt', 'blouse', 'tank_top', 'tube_top', 'crop_top',
                    'halter_top', 'strapless_top', 'off-shoulder', 'one-shoulder',
                    'sweater', 'pullover', 'turtleneck', 'hoodie', 'zip_up_hoodie',
                    'jacket', 'leather_jacket', 'blazer', 'suit_jacket', 'sport_jacket',
                    'coat', 'overcoat', 'trench_coat', 'duster', 'pea_coat',
                    'cardigan', 'vest', 'waistcoat', 'corset', 'bustier',
                    'sports_bra', 'athletic_shirt', 'polo_shirt',
                    'kimono_top', 'happi', 'haori', 'sarashi',
                    'no_shirt', 'shirtless', 'bare_shoulders', 'bare_back',
                    'off-shoulder_shirt', 'off-shoulder_sweater', 'off-shoulder_jacket',
                ],
                'nsfw' => false,
            ],

            'bottom' => [
                // Catches: miniskirt, pleated_skirt, pencil_skirt, sailor_skirt, denim_skirt, etc.
                // Also: sweatpants, track_pants, yoga_pants, etc.
                'regex'   => ['/_skirt$/', '/_pants$/'],
                'exact'   => [
                    'skirt', 'miniskirt', 'microskirt', 'short_skirt', 'long_skirt',
                    'frilled_skirt', 'tiered_skirt', 'layered_skirt', 'wrap_skirt',
                    'pants', 'jeans', 'trousers', 'slacks', 'wide-leg_pants',
                    'shorts', 'short_shorts', 'hot_pants', 'bermuda_shorts',
                    'cargo_pants', 'sweatpants', 'yoga_pants', 'track_pants',
                    'hakama', 'daisy_dukes', 'skort',
                    'bottomless', 'no_pants', 'no_skirt',
                ],
                'nsfw' => true,
            ],

            'dress' => [
                // Catches: wedding_dress, mini_dress, shirt_dress, sweater_dress, etc.
                'regex'   => ['/_dress$/', '/_gown$/'],
                'exact'   => [
                    'dress', 'sundress', 'cocktail_dress', 'ballgown',
                    'mini_dress', 'slip_dress', 'pinafore_dress', 'apron_dress',
                    'babydoll_dress', 'sailor_dress', 'lolita_fashion', 'gothic_lolita',
                    'sweet_lolita', 'classic_lolita', 'wa_lolita',
                    'kimono', 'furisode', 'houmongi', 'tomesode', 'yukata',
                    'qipao', 'hanfu', 'ao_dai', 'saree', 'dirndl',
                    'princess_dress', 'fairy_dress', 'prom_dress', 'evening_gown',
                ],
                'nsfw' => false,
            ],

            'uniform' => [
                'exact' => [
                    'school_uniform', 'serafuku', 'sailor_uniform', 'gakuran',
                    'blazer_uniform', 'military_uniform', 'army_uniform', 'naval_uniform',
                    'pilot_uniform', 'police_uniform', 'officer_uniform',
                    'nurse_uniform', 'doctor_uniform', 'lab_coat', 'surgical_scrubs',
                    'chef_uniform', 'waitress_uniform', 'flight_attendant_uniform',
                    'maid', 'maid_uniform', 'french_maid', 'maid_apron',
                    'butler', 'butler_uniform',
                    'shrine_maiden', 'miko', 'nun', 'priest', 'monk_robes',
                    'armor', 'knight_armor', 'plate_armor', 'chainmail',
                    'magical_girl', 'superhero', 'cheerleader', 'gym_uniform',
                    'pe_uniform', 'jersey', 'racing_suit', 'astronaut_suit', 'hazmat_suit',
                    'coveralls', 'jumpsuit', 'overalls',
                    'business_suit', 'suit', 'tuxedo',
                    'santa_costume', 'santa_dress', 'bunny_suit', 'playboy_bunny',
                    'cat_costume', 'witch_costume', 'vampire_costume', 'elf_costume',
                ],
                'nsfw' => false,
            ],

            'swimwear' => [
                'exact' => [
                    'bikini', 'micro_bikini', 'string_bikini', 'bandeau_bikini',
                    'strapless_bikini', 'sports_bikini', 'triangle_bikini',
                    'side-tie_bikini', 'g-string_bikini', 'o-ring_bikini', 'strappy_bikini',
                    'tankini', 'monokini', 'trikini', 'slingshot_swimsuit',
                    'one-piece_swimsuit', 'school_swimsuit', 'competition_swimsuit',
                    'rash_guard', 'wetsuit', 'swim_trunks', 'board_shorts',
                    'swim_briefs', 'bikini_top', 'bikini_bottom',
                ],
                'nsfw' => false,
            ],

            'headwear' => [
                // Catches: witch_hat, straw_hat, sun_hat, baseball_cap, nurse_cap, military_hat, etc.
                'regex'   => ['/_hat$/', '/_cap$/', '/_headdress$/', '/_hood$/'],
                'exact'   => [
                    'hat', 'cap', 'beret', 'beanie', 'bobble_hat', 'top_hat',
                    'fedora', 'bowler_hat', 'sombrero', 'visor', 'hardhat', 'helmet',
                    'battle_helmet', 'jester_cap', 'maid_headdress',
                    'veil', 'bridal_veil', 'tiara', 'crown', 'circlet',
                    'flower_crown', 'laurel_crown', 'antler_headdress',
                    'mask', 'half_mask', 'masquerade_mask', 'gas_mask',
                    'bunny_ears_headband', 'cat_ear_headband', 'devil_horns',
                    'hair_bow', 'hair_ribbon', 'hair_flower', 'hair_ornament',
                    'hair_clip', 'hairpin', 'barrette', 'headband', 'scrunchie', 'hood',
                ],
                'nsfw' => false,
            ],

            'handwear' => [
                'exact' => [
                    'gloves', 'fingerless_gloves', 'elbow_gloves', 'long_gloves',
                    'opera_gloves', 'lace_gloves', 'latex_gloves', 'rubber_gloves',
                    'mittens', 'half_gloves', 'gauntlets', 'bracers',
                    'black_gloves', 'white_gloves', 'red_gloves', 'blue_gloves',
                    'purple_gloves', 'single_glove',
                ],
                'nsfw' => false,
            ],

            'legwear' => [
                // Catches: white_thighhighs, striped_thighhighs, lace-top_thighhighs,
                // torn_thighhighs, fishnet_stockings, frilled_thighhighs, etc.
                'regex'   => ['/_thighhighs$/', '/_stockings$/', '/_socks$/', '/_pantyhose$/'],
                'exact'   => [
                    'thighhighs', 'over-knee_socks', 'knee_socks', 'ankle_socks',
                    'socks', 'no_socks', 'stockings', 'fishnet_stockings',
                    'pantyhose', 'fishnet_pantyhose', 'tights', 'leggings',
                    'garter_straps', 'leg_warmers', 'kneehighs', 'patterned_legwear',
                ],
                'nsfw' => false,
            ],

            'footwear' => [
                // Catches: high_heels, platform_heels, ankle_boots, combat_boots, running_shoes, etc.
                'regex'   => ['/_heels$/', '/_boots$/', '/_shoes$/', '/_sandals$/'],
                'exact'   => [
                    'heels', 'high_heels', 'stiletto_heels', 'kitten_heels',
                    'boots', 'knee_boots', 'thigh_boots', 'over-knee_boots',
                    'sneakers', 'flats', 'ballet_flats', 'loafers', 'oxfords',
                    'mary_janes', 'pumps', 'mules', 'sandals', 'flip_flops',
                    'slippers', 'moccasins', 'clogs', 'geta', 'zori',
                    'no_shoes', 'barefoot',
                ],
                'nsfw' => false,
            ],

            'accessories' => [
                'exact' => [
                    'necklace', 'choker', 'pendant', 'locket', 'pearl_necklace',
                    'chain_necklace', 'cross_necklace', 'gem_necklace',
                    'earrings', 'stud_earrings', 'hoop_earrings', 'drop_earrings',
                    'clip-on_earrings', 'chandelier_earrings',
                    'bracelet', 'bangle', 'cuff', 'wristband', 'charm_bracelet',
                    'ring', 'rings', 'engagement_ring', 'wedding_ring', 'anklet',
                    'bag', 'handbag', 'purse', 'clutch', 'backpack', 'shoulder_bag',
                    'tote_bag', 'satchel', 'briefcase',
                    'scarf', 'muffler', 'shawl', 'stole',
                    'belt', 'waist_belt', 'garter_belt', 'suspenders',
                    'ribbon', 'bow', 'bow_tie', 'necktie', 'bolo_tie',
                    'watch', 'pocket_watch', 'armband',
                    'cape', 'cloak', 'mantle', 'poncho',
                    'parasol', 'umbrella', 'fan', 'handheld_fan',
                ],
                'nsfw' => false,
            ],

            'eyewear' => [
                'exact' => [
                    'glasses', 'sunglasses', 'monocle', 'goggles', 'safety_glasses',
                    'half-rim_glasses', 'rimless_glasses', 'cat-eye_glasses',
                    'round_glasses', 'rectangular_glasses', 'star-shaped_glasses',
                    'heart-shaped_eyewear', 'colored_glasses', 'reading_glasses',
                ],
                'nsfw' => false,
            ],

            'neckwear' => [
                'exact' => [
                    'collar', 'dog_collar', 'cat_collar', 'slave_collar',
                    'spiked_collar', 'bell_collar', 'ribbon_collar',
                    'necktie', 'neck_ribbon', 'cravat', 'ascot', 'ruff',
                    'neck_warmer', 'turtleneck',
                ],
                'nsfw' => false,
            ],

            'sleeves' => [
                'exact' => [
                    'long_sleeves', 'short_sleeves', 'sleeveless', 'no_sleeves',
                    'half_sleeves', 'puffed_sleeves', 'bell_sleeves', 'kimono_sleeves',
                    'rolled_up_sleeves', 'detached_sleeves', 'single_sleeve',
                    'arm_warmers', 'fingerless_arm_warmers',
                ],
                'nsfw' => false,
            ],

            'makeup' => [
                'exact' => [
                    'lipstick', 'red_lips', 'dark_lipstick', 'lip_gloss',
                    'eyeshadow', 'eyeliner', 'mascara', 'false_eyelashes',
                    'blush_(makeup)', 'contouring', 'highlighter', 'foundation',
                    'nail_polish', 'painted_nails', 'french_manicure',
                    'facial_mark', 'beauty_mark', 'war_paint',
                ],
                'nsfw' => false,
            ],

            'sexual_attire' => [
                // Catches: see-through_clothes, see-through_shirt, torn_clothes, torn_pantyhose,
                // shirt_lift, skirt_lift, taut_shirt, taut_dress, popping_button, wet_shirt, etc.
                'regex'   => [
                    '/^see-through/',
                    '/_lift$/',
                    '/_pull$/',
                    '/^torn_/',
                    '/^ripped_/',
                    '/^wet_/',
                    '/^taut_/',
                    '/^popping_/',
                    '/^bursting_/',
                    '/^clothes_/',
                ],
                'exclude' => ['hair_lift', 'face_lift', 'breast_lift'],
                'exact'   => [
                    'naked_apron', 'pasties', 'nipple_tape', 'bodypaint',
                    'see-through', 'sheer_fabric', 'panties_around_legs', 'panties_aside',
                    'barely_clothed', 'revealing_clothes', 'underboob', 'sideboob',
                    'latex', 'pvc', 'leather_outfit', 'bondage_outfit',
                    'catsuit', 'zentai', 'harness', 'body_harness',
                    'nude', 'naked', 'completely_nude', 'topless', 'topless_female',
                    'bottomless', 'naked_coat', 'no_bra', 'braless',
                    'bra', 'strapless_bra', 'see-through_bra',
                    'panties', 'thong', 'g-string', 'boyshorts', 'bloomers',
                    'fundoshi', 'no_panties', 'commando',
                    'lingerie', 'negligee', 'chemise', 'babydoll', 'teddy', 'bodystocking',
                    'taut_clothes', 'straining_buttons', 'clothes_too_small',
                    'fabric_pull', 'clothes_writing',
                ],
                'nsfw' => true,
            ],

            'fashion_style' => [
                'exact' => [
                    'casual', 'formal', 'business_casual', 'streetwear', 'athleisure',
                    'preppy', 'bohemian', 'hipster', 'punk', 'gothic', 'emo',
                    'harajuku', 'gyaru', 'visual_kei', 'kogal', 'cosplay',
                    'futuristic', 'cyberpunk', 'steampunk', 'dieselpunk',
                    'cottagecore', 'dark_academia', 'fairy_grunge',
                    'pin-up', 'vintage', 'retro',
                ],
                'nsfw' => false,
            ],

            'embellishment' => [
                'exact' => [
                    'frills', 'lace', 'ruffles', 'pleats', 'embroidery',
                    'sequins', 'beading', 'rhinestones', 'studs', 'patches',
                    'print', 'logo', 'stripe', 'plaid', 'polka_dot',
                    'floral_print', 'checkered', 'houndstooth',
                ],
                'nsfw' => false,
            ],

        ],

        // ════════════════════════════════════════════════════════════════
        // SECTION: scene
        // ════════════════════════════════════════════════════════════════

        'scene' => [

            'outdoor_nature' => [
                'exact' => [
                    'outdoors', 'nature', 'forest', 'jungle', 'woods', 'bamboo_forest',
                    'park', 'garden', 'botanical_garden', 'flower_field', 'meadow',
                    'grass', 'grassland', 'savanna',
                    'hill', 'mountain', 'mountain_range', 'cliff', 'canyon', 'valley',
                    'cave', 'grotto',
                    'waterfall', 'river', 'stream', 'creek',
                    'lake', 'pond', 'swamp', 'marsh',
                    'ocean', 'sea', 'coast', 'beach', 'shore',
                    'island', 'tropical_island',
                    'desert', 'sand_dunes', 'oasis',
                    'snow', 'snowfield', 'glacier', 'ice', 'tundra',
                    'cherry_blossoms', 'autumn_leaves', 'bamboo', 'palm_tree', 'cactus',
                    'sky', 'clouds', 'rainbow', 'starry_sky', 'aurora', 'galaxy',
                    'horizon', 'sunrise', 'sunset', 'twilight',
                    'moon', 'full_moon', 'crescent_moon', 'sun', 'sunbeam',
                    'underwater', 'seabed', 'coral_reef',
                ],
                'nsfw' => false,
            ],

            'outdoor_urban' => [
                'exact' => [
                    'city', 'cityscape', 'urban', 'metropolis', 'downtown',
                    'street', 'road', 'sidewalk', 'alley', 'back_alley',
                    'rooftop', 'fire_escape', 'skyscraper', 'building',
                    'bridge', 'overpass', 'highway',
                    'train_station', 'bus_stop', 'subway_station', 'platform',
                    'airport', 'harbor', 'dock', 'pier',
                    'parking_lot', 'playground', 'amusement_park', 'carnival',
                    'festival', 'market', 'night_market', 'shopping_district',
                    'plaza', 'courtyard', 'fountain', 'monument',
                    'cemetery', 'graveyard', 'shrine', 'torii_gate', 'japanese_street',
                    'construction_site', 'factory', 'warehouse',
                ],
                'nsfw' => false,
            ],

            'indoor_home' => [
                'exact' => [
                    'indoors', 'house', 'home', 'apartment',
                    'bedroom', 'bed', 'bunk_bed',
                    'living_room', 'couch', 'sofa', 'armchair',
                    'kitchen', 'dining_table', 'dining_room', 'counter',
                    'bathroom', 'shower', 'bathtub', 'toilet', 'sink', 'mirror',
                    'hallway', 'corridor', 'entrance',
                    'stairs', 'staircase', 'attic', 'basement',
                    'balcony', 'terrace', 'porch',
                    'windowsill', 'window', 'curtains',
                    'fireplace', 'bookshelf', 'desk', 'chair',
                    'carpet', 'wooden_floor', 'tatami', 'japanese_room',
                    'garage', 'laundry_room', 'closet',
                ],
                'nsfw' => false,
            ],

            'indoor_public' => [
                'exact' => [
                    'classroom', 'school', 'school_hallway', 'locker', 'gymnasium',
                    'library', 'bookstore',
                    'office', 'cubicle', 'conference_room', 'reception',
                    'laboratory', 'lab', 'research_facility',
                    'hospital', 'clinic', 'waiting_room', 'operating_room',
                    'cafe', 'coffee_shop', 'bakery',
                    'restaurant', 'diner', 'izakaya', 'bar', 'pub',
                    'nightclub', 'dance_floor',
                    'gym', 'fitness_center', 'yoga_studio', 'boxing_ring',
                    'swimming_pool', 'locker_room', 'changing_room',
                    'hotel_room', 'hotel', 'resort', 'ryokan',
                    'spa', 'sauna', 'onsen', 'sento', 'bathhouse',
                    'arcade', 'game_center', 'bowling_alley',
                    'movie_theater', 'concert_hall', 'stage', 'theater',
                    'museum', 'art_gallery',
                    'church', 'cathedral', 'chapel',
                    'temple', 'mosque',
                    'prison', 'dungeon_cell',
                    'love_hotel',
                ],
                'nsfw' => true,
            ],

            'fantasy' => [
                'exact' => [
                    'fantasy', 'high_fantasy', 'dark_fantasy',
                    'medieval', 'castle', 'castle_interior', 'throne_room',
                    'dungeon', 'crypt', 'tomb',
                    'magic_circle', 'rune', 'magical_circle',
                    'enchanted_forest', 'mystical_forest',
                    'floating_island', 'sky_island',
                    'ancient_ruins', 'temple_ruins',
                    'tavern', 'magic_shop', 'alchemy_lab',
                    'witch_house', 'haunted_house', 'ghost_mansion',
                    'underworld', 'void', 'portal',
                    'dragon_lair', 'monster_den',
                    'sacred_ground', 'sanctuary',
                    'colosseum', 'arena', 'pirate_ship', 'ghost_ship',
                ],
                'nsfw' => false,
            ],

            'scifi' => [
                'exact' => [
                    'sci-fi', 'futuristic', 'cyberpunk', 'dystopia', 'post-apocalyptic',
                    'space', 'outer_space', 'spaceship', 'space_station', 'space_colony',
                    'planet', 'alien_world', 'asteroid', 'nebula',
                    'cockpit', 'engine_room', 'cargo_bay',
                    'server_room', 'control_room',
                    'mech', 'mecha', 'giant_robot', 'power_armor', 'exosuit',
                    'hologram', 'holographic_display', 'cyberspace',
                    'virtual_reality', 'augmented_reality',
                    'neon_lights', 'neon_signs', 'glowing_lines',
                    'android', 'cyborg', 'robot',
                ],
                'nsfw' => false,
            ],

            'image_composition' => [
                'exact' => [
                    'masterpiece', 'best_quality', 'high_quality', 'ultra_detailed',
                    'detailed', 'intricate', 'extremely_detailed',
                    'realistic', 'photorealistic', 'hyperrealistic', 'semi-realistic',
                    'anime', 'manga', 'cartoon', 'illustration', 'digital_art',
                    'oil_painting', 'watercolor', 'acrylic',
                    'sketch', 'line_art', 'ink', 'pencil_drawing',
                    'cel_shading', 'flat_color', 'flat_shading',
                    'monochrome', 'grayscale', 'sepia',
                    'vintage', 'retro', 'film_grain',
                    'pixel_art', '8-bit', '16-bit',
                    '3dcg', 'render', 'concept_art', 'official_art',
                    '8k', '4k', 'absurdres', 'highres', 'hd',
                    'simple_background', 'white_background', 'black_background',
                    'gradient_background', 'bokeh', 'depth_of_field', 'blurry_background',
                ],
                'nsfw' => false,
            ],

            'lighting' => [
                'exact' => [
                    'sunlight', 'moonlight', 'starlight', 'candlelight', 'lamplight',
                    'backlighting', 'rim_lighting', 'soft_lighting', 'harsh_lighting',
                    'dim_lighting', 'dark', 'darkness', 'shadows',
                    'spotlight', 'studio_lighting', 'natural_light',
                    'neon_lights', 'bioluminescence', 'glowing', 'sparkle',
                    'light_rays', 'god_rays', 'lens_flare', 'bloom',
                    'dramatic_lighting', 'moody_lighting', 'cinematic_lighting',
                    'volumetric_lighting', 'fire_light',
                    'blue_light', 'red_light', 'green_light',
                    'purple_light', 'warm_light', 'cool_light',
                ],
                'nsfw' => false,
            ],

            'colors' => [
                'exact' => [
                    'colorful', 'vibrant', 'vivid', 'pastel', 'muted', 'desaturated',
                    'warm_colors', 'cool_colors', 'earth_tones',
                    'monochromatic',
                    'black', 'white', 'red', 'blue', 'green', 'yellow', 'purple',
                    'orange', 'pink', 'brown', 'grey', 'gold', 'silver', 'bronze',
                    'rainbow', 'multicolored', 'gradient', 'ombre',
                ],
                'nsfw' => false,
            ],

            'atmosphere' => [
                'exact' => [
                    'day', 'daytime', 'morning', 'afternoon', 'evening',
                    'dusk', 'twilight', 'night', 'midnight', 'dawn', 'sunrise', 'sunset',
                    'blue_hour', 'golden_hour', 'noon',
                    'clear_sky', 'cloudy', 'overcast', 'partly_cloudy',
                    'rain', 'heavy_rain', 'drizzle', 'storm', 'thunderstorm', 'lightning',
                    'snow', 'blizzard', 'fog', 'mist', 'haze',
                    'wind', 'windy', 'hail',
                    'sunny', 'cold', 'freezing',
                    'spring', 'summer', 'autumn', 'fall', 'winter',
                ],
                'nsfw' => false,
            ],

            'locations' => [
                'exact' => [
                    'tokyo', 'kyoto', 'osaka', 'akihabara', 'shibuya', 'shinjuku',
                    'new_york', 'paris', 'london', 'los_angeles', 'rome', 'berlin',
                    'shanghai', 'hong_kong', 'seoul', 'bangkok',
                    'japan', 'china', 'korea', 'europe', 'america',
                    'countryside', 'rural', 'village', 'town',
                ],
                'nsfw' => false,
            ],

            'objects' => [
                'exact' => [
                    'weapon', 'sword', 'katana', 'knife', 'dagger', 'spear',
                    'bow', 'arrow', 'crossbow', 'gun', 'pistol', 'rifle', 'shotgun',
                    'axe', 'hammer', 'mace', 'whip', 'staff', 'wand',
                    'book', 'tome', 'scroll', 'letter',
                    'cup', 'mug', 'wine_glass', 'bottle', 'can', 'teapot',
                    'food', 'fruit', 'cake', 'ice_cream', 'candy', 'chocolate',
                    'flower', 'bouquet', 'rose', 'lily', 'sunflower', 'cherry_blossom',
                    'cat', 'dog', 'rabbit', 'bird', 'fish', 'dragon',
                    'phone', 'smartphone', 'camera', 'laptop', 'computer',
                    'car', 'motorcycle', 'bicycle', 'train', 'airplane', 'ship',
                    'money', 'coin', 'gem', 'crystal',
                    'mirror', 'hourglass', 'clock',
                ],
                'nsfw' => false,
            ],

        ],
    ];

    public function handle(): int
    {
        $sectionFilter = $this->option('section');
        $groupFilter   = $this->option('group');
        $fresh         = $this->option('fresh');
        $minCount      = (int) $this->option('min-count');

        if ($sectionFilter && !array_key_exists($sectionFilter, $this->map)) {
            $this->error('Invalid section. Valid: ' . implode(', ', array_keys($this->map)));
            return self::FAILURE;
        }

        // ── 1. Load CSV ───────────────────────────────────────────────
        $this->info('Loading CSV…');
        $tags = $this->loadCsvTags($minCount);

        if (empty($tags)) {
            $this->error('Could not load any tags from CSV.');
            return self::FAILURE;
        }

        $this->info(sprintf('  Loaded %s general tags (post_count ≥ %d)', number_format(count($tags)), $minCount));

        // ── 2. Optional fresh wipe ────────────────────────────────────
        if ($fresh) {
            $query = Tag::query();
            if ($sectionFilter) $query->where('section', $sectionFilter);
            if ($groupFilter)   $query->where('subsection', $groupFilter);
            $deleted = $query->delete();
            $this->info("Deleted {$deleted} existing tags.");
        }

        // ── 3. Match tags to subsections ──────────────────────────────
        $this->info('Matching tags to subsections…');
        $sections = $sectionFilter ? [$sectionFilter => $this->map[$sectionFilter]] : $this->map;

        // Track which tags have already been assigned (first match wins).
        $assigned  = [];
        $batches   = []; // subsection_key => [ [name, section, subsection, post_count, is_nsfw], … ]

        foreach ($sections as $section => $groups) {
            foreach ($groups as $subsection => $config) {
                if ($groupFilter && $subsection !== $groupFilter) continue;

                $isNsfw  = $config['nsfw'];
                $exclude = $config['exclude'] ?? [];
                $matched = [];

                // Exact matches (highest priority)
                foreach ($config['exact'] ?? [] as $name) {
                    if (isset($tags[$name]) && !isset($assigned[$name]) && !in_array($name, $exclude)) {
                        $matched[]      = $name;
                        $assigned[$name] = true;
                    }
                }

                // Regex pattern matches
                foreach ($config['regex'] ?? [] as $pattern) {
                    foreach ($tags as $name => $postCount) {
                        if (isset($assigned[$name])) continue;
                        if (in_array($name, $exclude)) continue;
                        if (preg_match($pattern, $name)) {
                            $matched[]       = $name;
                            $assigned[$name] = true;
                        }
                    }
                }

                if (!empty($matched)) {
                    $batches["{$section}/{$subsection}"] = [
                        'section'    => $section,
                        'subsection' => $subsection,
                        'is_nsfw'    => $isNsfw,
                        'names'      => $matched,
                    ];
                }
            }
        }

        // ── 4. Upsert into DB ─────────────────────────────────────────
        $this->info('Inserting into database…');
        $totalInserted = 0;
        $totalUpdated  = 0;

        foreach ($batches as $key => $batch) {
            $inserted = 0;
            $updated  = 0;

            foreach ($batch['names'] as $name) {
                $postCount = $tags[$name];

                $result = Tag::updateOrCreate(
                    ['name' => $name],
                    [
                        'section'    => $batch['section'],
                        'subsection' => $batch['subsection'],
                        'post_count' => $postCount,
                        'is_nsfw'    => $batch['is_nsfw'],
                    ]
                );

                $result->wasRecentlyCreated ? $inserted++ : $updated++;
            }

            $totalInserted += $inserted;
            $totalUpdated  += $updated;
            $this->line("  [{$key}] +{$inserted} new, {$updated} updated");
        }

        $this->newLine();
        $this->info("Done. Inserted: {$totalInserted} | Updated: {$totalUpdated} | Total in DB: " . Tag::count());

        return self::SUCCESS;
    }

    /**
     * Download (or read) the CSV and return an array of [ tag_name => post_count ]
     * filtered to category=0 (General) and post_count >= $minCount.
     *
     * CSV format (no header row): name,category,post_count,aliases
     */
    private function loadCsvTags(int $minCount): array
    {
        $localFile = $this->option('file');

        if ($localFile) {
            if (!file_exists($localFile)) {
                $this->error("Local file not found: {$localFile}");
                return [];
            }
            $raw = file_get_contents($localFile);
        } else {
            $url = $this->option('url') ?: static::DEFAULT_CSV_URL;
            $this->line("  Downloading: {$url}");

            try {
                $response = Http::timeout(120)->get($url);
                if (!$response->successful()) {
                    $this->error("HTTP {$response->status()} fetching CSV.");
                    return [];
                }
                $raw = $response->body();
            } catch (\Exception $e) {
                $this->error('Download failed: ' . $e->getMessage());
                return [];
            }
        }

        $tags = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $parts = str_getcsv($line);
            if (count($parts) < 3) continue;

            $name      = trim($parts[0]);
            $category  = (int) ($parts[1] ?? -1);
            $postCount = (int) ($parts[2] ?? 0);

            // Keep only General (category=0) with sufficient post count
            if ($category !== 0) continue;
            if ($postCount < $minCount) continue;
            if ($name === '') continue;

            $tags[$name] = $postCount;
        }

        return $tags;
    }
}
