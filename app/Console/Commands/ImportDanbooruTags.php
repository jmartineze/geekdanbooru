<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportDanbooruTags extends Command
{
    protected $signature = 'danbooru:import
                            {--section= : Import only a specific section (character|pose|outfit|scene)}
                            {--group=   : Import only a specific subsection/group slug}
                            {--fresh    : Delete existing tags in the target scope before importing}
                            {--min-count=100 : Minimum post count to include a tag}';

    protected $description = 'Import tags from the Danbooru public API, organized by section and tag group';

    /**
     * Full tag map following Danbooru's official tag group structure.
     *
     * Each entry: [ 'patterns' => [...], 'exact' => [...], 'exclude' => [...], 'nsfw' => bool ]
     * 'patterns' → wildcard search via Danbooru API (e.g. "*_hair")
     * 'exact'    → specific tag names seeded directly (no API call per tag)
     */
    private array $map = [

        // ════════════════════════════════════════════════════════════════
        // SECTION: character  (Visual characteristics > Body)
        // ════════════════════════════════════════════════════════════════

        'character' => [

            // Tag group:Hair color
            'hair_color' => [
                'patterns' => ['*_hair'],
                'exclude'  => [
                    'hair', 'hair_ornament', 'hair_ribbon', 'hair_bow', 'hair_bun',
                    'hair_flower', 'hair_tie', 'hair_between_eyes', 'hair_over_one_eye',
                    'hair_intakes', 'hair_tubes', 'hair_up', 'hair_down', 'hair_spread_out',
                    'hair_censor', 'hair_over_breasts', 'hair_over_shoulder',
                    'dyed_hair', 'natural_hair', 'twin_hair_buns',
                ],
                'nsfw' => false,
            ],

            // Tag group:Hair styles
            'hair_style' => [
                'exact' => [
                    'twintails', 'low_twintails', 'high_twintails', 'side_ponytail',
                    'low_ponytail', 'high_ponytail', 'ponytail', 'braid', 'french_braid',
                    'single_braid', 'twin_braids', 'side_braid', 'crown_braid', 'fishtail_braid',
                    'ahoge', 'drill_hair', 'twin_drills', 'ringlets', 'hime_cut',
                    'bangs', 'blunt_bangs', 'swept_bangs', 'parted_bangs', 'crossed_bangs',
                    'asymmetrical_bangs', 'sidelocks', 'two_side_up', 'hair_bun', 'double_bun',
                    'single_hair_bun', 'cone_hair_bun', 'folded_ponytail', 'interlocked_fingers_ponytail',
                    'messy_hair', 'wavy_hair', 'curly_hair', 'straight_hair', 'spiky_hair',
                    'bob_cut', 'pixie_cut', 'undercut', 'mohawk', 'dreadlocks',
                    'hair_rings', 'hair_up', 'hair_over_one_eye', 'hair_between_eyes',
                    'front_ponytail', 'low_twin_braids', 'odango', 'huge_ahoge',
                ],
                'nsfw' => false,
            ],

            // Tag group:Hair (length)
            'hair_length' => [
                'exact' => [
                    'short_hair', 'medium_hair', 'long_hair', 'very_long_hair',
                    'absurdly_long_hair', 'shoulder-length_hair', 'neck-length_hair',
                ],
                'nsfw' => false,
            ],

            // Tag group:Eyes tags
            'eyes' => [
                'patterns' => ['*_eyes'],
                'exclude'  => [
                    'eyes', 'eye', 'eyeball', 'empty_eyes', 'glowing_eyes',
                    'multiple_eyes', 'covering_eyes', 'peeking_eyes',
                ],
                'also_exact' => [
                    'heterochromia', 'multicolored_eyes', 'glowing_eyes', 'empty_eyes',
                    'aqua_eyes', 'amber_eyes', 'tareme', 'tsurime', 'sanpaku',
                    'half-closed_eyes', 'wide_eyes', 'wink', 'one_eye_closed',
                    'heart-shaped_eyes', 'star-shaped_pupils', 'slit_pupils', 'no_pupils',
                    'ringed_eyes', 'sparkling_eyes', 'tears', 'watery_eyes', 'crying',
                    'dreaming', 'dull_eyes', 'light_purple_eyes', 'dark_blue_eyes',
                ],
                'nsfw' => false,
            ],

            // Tag group:Face tags
            'face' => [
                'exact' => [
                    // Expressions
                    'smile', 'grin', 'smirk', 'laughing', 'giggling', 'happy', 'joyful',
                    'blush', 'embarrassed', 'shy', 'nervous', 'surprised', 'shocked',
                    'open_mouth', 'closed_mouth', 'pout', 'angry', 'annoyed', ':>', ':3',
                    ':o', ';)', ':d', ':p', 'xd', '^_^', '>_<', 'uwu',
                    'sad', 'crying', 'tears', 'lonely', 'scared', 'fearful',
                    'confused', 'thinking', 'serious', 'stoic', 'expressionless',
                    'blank_stare', 'seductive_smile', 'sultry', 'tongue_out', 'licking_lips',
                    // Facial features
                    'freckles', 'mole', 'mole_under_eye', 'mole_on_cheek', 'dimples',
                    'fangs', 'pointed_teeth', 'sharp_teeth', 'buck_teeth', 'gap_teeth',
                    'nose_blush', 'flushed', 'pale', 'blush_stickers',
                    // Makeup / face accessories
                    'lipstick', 'eyeshadow', 'eyeliner', 'mascara', 'blush_(makeup)',
                    'nose_ring', 'facial_mark', 'facial_hair', 'beard', 'mustache',
                ],
                'nsfw' => false,
            ],

            // Tag group:Ears tags
            'ears' => [
                'exact' => [
                    'cat_ears', 'dog_ears', 'fox_ears', 'bunny_ears', 'wolf_ears',
                    'horse_ears', 'bear_ears', 'mouse_ears', 'deer_ears', 'tiger_ears',
                    'lion_ears', 'sheep_ears', 'cow_ears', 'pig_ears', 'bat_ears',
                    'dragon_ears', 'elf_ears', 'pointy_ears', 'large_ears',
                    'floppy_ears', 'animal_ears', 'kemonomimi_mode',
                    'ear_piercing', 'ear_blush', 'ear_licking',
                ],
                'nsfw' => false,
            ],

            // Tag group:Body parts (general + skin color + body type)
            'body' => [
                'exact' => [
                    // body type
                    'slim', 'skinny', 'petite', 'curvy', 'plump', 'chubby', 'fat',
                    'muscular', 'muscular_female', 'toned', 'abs', 'broad_shoulders',
                    'narrow_waist', 'wide_hips', 'thick_thighs', 'long_legs', 'short_stature',
                    // count/age
                    '1girl', '1boy', '2girls', '2boys', '3girls', '3boys',
                    'multiple_girls', 'multiple_boys', 'solo', 'solo_focus',
                    'mature_female', 'older_female', 'younger_female',
                    'milf', 'loli', 'shota', 'young', 'adult', 'elder',
                    // general body tags
                    'navel', 'belly', 'stomach', 'waist', 'hip', 'back', 'spine',
                    'armpit', 'collarbone', 'shoulder', 'neck',
                    'leg', 'thigh', 'knee', 'calf', 'ankle',
                    'arm', 'elbow', 'wrist',
                    // wings / tails
                    'angel_wings', 'demon_wings', 'dragon_wings', 'fairy_wings',
                    'bird_wings', 'bat_wings', 'feathered_wings', 'single_wing',
                    'cat_tail', 'fox_tail', 'dog_tail', 'bunny_tail', 'wolf_tail',
                    'horse_tail', 'cow_tail', 'demon_tail', 'dragon_tail', 'multiple_tails',
                    // horns / special
                    'horns', 'demon_horns', 'dragon_horns', 'oni_horns', 'single_horn',
                    'halo', 'third_eye', 'cyclops', 'aura', 'glowing', 'scales',
                    'scar', 'tattoo', 'body_markings', 'birthmark', 'wound',
                ],
                'nsfw' => false,
            ],

            // Tag group:Skin color
            'skin_color' => [
                'exact' => [
                    'dark_skin', 'dark-skinned_female', 'dark-skinned_male',
                    'pale_skin', 'tan', 'tanned', 'light_skin', 'olive_skin',
                    'brown_skin', 'white_skin', 'fair_skin', 'ebony_skin',
                ],
                'nsfw' => false,
            ],

            // Tag group:Breasts tags [NSFW]
            'breasts' => [
                // Pattern captures: large_breasts, huge_breasts, bursting_breasts,
                // bouncing_breasts, hanging_breasts, saggy_breasts, etc.
                'patterns' => ['*_breasts'],
                'exclude'  => ['breasts'],
                'exact' => [
                    'flat_chest', 'cleavage', 'deep_cleavage', 'sideboob', 'underboob',
                    'topless', 'naked_breasts', 'nipples', 'areolae', 'erect_nipples',
                    'big_areolae', 'puffy_nipples', 'inverted_nipples', 'dark_areolae',
                    'breast_focus',
                ],
                'nsfw' => true,
            ],

            // Tag group:Ass [NSFW]
            'ass' => [
                'patterns' => ['*_ass', 'ass_*'],
                'exclude'  => ['ass'],
                'exact' => [
                    'ass', 'butt', 'buttocks', 'rear_end',
                    'butt_crack', 'ass_cleavage', 'pantylines', 'cameltoe',
                    'presenting_ass', 'spreading', 'ass_shake',
                    'anal', 'anal_object_insertion', 'butt_plug', 'tail_plug',
                    // Clothing stress on ass
                    'taut_pants', 'taut_shorts', 'taut_skirt', 'taut_leggings',
                    'pants_too_small', 'shorts_too_small', 'skirt_too_small',
                ],
                'nsfw' => true,
            ],

            // Tag group:Pussy [NSFW]
            'pussy' => [
                'exact' => [
                    'pussy', 'vagina', 'spread_pussy', 'wet_pussy', 'dripping',
                    'pubic_hair', 'shaved_pussy', 'hairless_pussy', 'censored',
                    'uncensored', 'pussy_juice', 'squirting', 'female_ejaculation',
                    'fingering', 'pussy_licking', 'cunnilingus', 'dildo',
                    'vibrator', 'sex_toy', 'object_insertion', 'vaginal_object_insertion',
                    'penis_in_pussy', 'vaginal', 'creampie',
                ],
                'nsfw' => true,
            ],

            // Tag group:Hands + Gestures
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
                ],
                'nsfw' => false,
            ],

            // Tag group:Feet
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
        // SECTION: pose  (Posture + Sex acts + Expressions)
        // ════════════════════════════════════════════════════════════════

        'pose' => [

            // Tag group:Posture — standing
            'standing' => [
                'exact' => [
                    'standing', 'contrapposto', 'weight_shift', 'walking', 'running',
                    'jumping', 'floating', 'flying', 'hovering', 'on_tiptoes',
                    'stomping', 'lunging', 'leaning_forward', 'leaning_back',
                    'leaning_to_the_side', 'leaning_on_object', 'back-to-back',
                    'marching', 'tiptoeing', 'crouching', 'squatting', 'kneeling',
                    'on_one_knee', 'genuflect', 'warrior_pose',
                ],
                'nsfw' => false,
            ],

            // Tag group:Posture — sitting
            'sitting' => [
                'exact' => [
                    'sitting', 'seiza', 'wariza', 'indian_style', 'cross-legged',
                    'hugging_knees', 'sitting_on_ground', 'sitting_on_bench',
                    'sitting_on_table', 'sitting_on_chair', 'sitting_on_person',
                    'perching', 'throne', 'lotus_position', 'tailor_sitting',
                ],
                'nsfw' => false,
            ],

            // Tag group:Posture — lying
            'lying' => [
                'exact' => [
                    'lying', 'on_back', 'on_stomach', 'on_side', 'fetal_position',
                    'sleeping', 'reclining', 'spread_eagle', 'arched_back',
                    'all_fours', 'bent_over', 'prone_bone', 'doggy_style',
                    'supine', 'face_up', 'face_down',
                ],
                'nsfw' => true,
            ],

            // Tag group:Posture — leg position
            'leg_position' => [
                'exact' => [
                    'legs_up', 'legs_together', 'legs_apart', 'spread_legs',
                    'crossed_legs', 'knees_together', 'knees_up', 'knee_up',
                    'leg_lift', 'standing_split', 'leg_lock', 'thigh_squeeze',
                    'feet_up', 'feet_together', 'pigeon_toed',
                    'figure_four_sitting', 'open_legs', 'closed_legs',
                ],
                'nsfw' => true,
            ],

            // Tag group:Posture — camera & framing
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

            // Tag group:Sex acts [NSFW]
            'sex_acts' => [
                'patterns' => ['cum_*', '*_job', 'breast_*', '*_penetration'],
                'exclude'  => ['breast_pocket', 'breast_plate', 'breast_armor'],
                'exact' => [
                    'sex', 'vaginal', 'anal', 'oral', 'fellatio', 'cunnilingus',
                    '69', 'handjob', 'paizuri', 'footjob', 'armpit_sex', 'frottage',
                    'tribadism', 'masturbation', 'fingering', 'penetration',
                    'double_penetration', 'triple_penetration', 'gangbang', 'group_sex',
                    'threesome', 'foursome', 'orgy', 'creampie', 'cum_inside',
                    'ejaculation', 'cumshot', 'facial', 'cum_on_breasts', 'cum_on_ass',
                    'cum_on_body', 'cum_on_clothes', 'swallowing', 'snowball',
                    'pegging', 'prostate_massage', 'deepthroat', 'irrumatio',
                    'self_fellatio', 'autofellatio',
                    // breast interactions (moved from breasts subsection)
                    'breast_grab', 'breast_press', 'breast_squeeze', 'breast_hold',
                    'breast_lift', 'bouncing_breasts', 'breast_expansion',
                    'presenting', 'pov_hands',
                ],
                'nsfw' => true,
            ],

            // Tag group:Sexual positions [NSFW]
            'sexual_positions' => [
                'exact' => [
                    'missionary', 'doggy_style', 'cowgirl_position',
                    'reverse_cowgirl_position', 'standing_sex', 'prone_bone',
                    'mating_press', 'spooning', 'amazon_position', 'pile_driver',
                    'lotus_position_(sex)', 'side_sex', 'face_sitting',
                    'sitting_sex', 'wall_sex', 'desk_sex', 'shower_sex',
                    'bathtub_sex', 'standing_doggy_style', 'standing_missionary',
                    'chairwoman_position', 'lap_dance', 'riding',
                ],
                'nsfw' => true,
            ],

            // Tag group:BDSM and torture [NSFW]
            'bdsm' => [
                'exact' => [
                    'bondage', 'restrained', 'tied_up', 'rope_bondage', 'shibari',
                    'handcuffed', 'cuffed', 'chained', 'blindfolded', 'gagged',
                    'ballgag', 'collar_and_leash', 'leash', 'collar',
                    'spanking', 'whipping', 'paddling', 'flogging',
                    'slave', 'pet_play', 'pony_play', 'maid_discipline',
                    'dominatrix', 'master_and_slave', 'submissive', 'dominant',
                    'orgasm_denial', 'edging', 'forced_orgasm',
                    'vibrator_under_clothes', 'public_use', 'objectification',
                    'sex_machine', 'milking_machine',
                ],
                'nsfw' => true,
            ],

        ],

        // ════════════════════════════════════════════════════════════════
        // SECTION: outfit  (Attire and body accessories)
        // ════════════════════════════════════════════════════════════════

        'outfit' => [

            // Tag group:Attire — tops
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
                ],
                'nsfw' => false,
            ],

            // Tag group:Attire — bottoms
            'bottom' => [
                'exact' => [
                    'skirt', 'miniskirt', 'microskirt', 'pleated_skirt', 'pencil_skirt',
                    'a-line_skirt', 'circle_skirt', 'wrap_skirt', 'layered_skirt',
                    'short_skirt', 'long_skirt', 'frilled_skirt', 'tiered_skirt',
                    'pants', 'jeans', 'trousers', 'slacks', 'wide-leg_pants',
                    'shorts', 'short_shorts', 'hot_pants', 'bermuda_shorts',
                    'cargo_pants', 'sweatpants', 'yoga_pants', 'track_pants',
                    'hakama', 'daisy_dukes', 'skort',
                    'bottomless', 'no_pants', 'no_skirt', 'micro_skirt',
                ],
                'nsfw' => true,
            ],

            // Tag group:Attire — dresses & full body
            'dress' => [
                'exact' => [
                    'dress', 'sundress', 'cocktail_dress', 'evening_gown', 'ballgown',
                    'wedding_dress', 'mini_dress', 'shirt_dress', 'sweater_dress',
                    'wrap_dress', 'slip_dress', 'pinafore_dress', 'apron_dress',
                    'babydoll_dress', 'sailor_dress', 'lolita_fashion', 'gothic_lolita',
                    'sweet_lolita', 'classic_lolita', 'wa_lolita', 'brolita',
                    'kimono', 'furisode', 'houmongi', 'tomesode', 'yukata',
                    'qipao', 'hanfu', 'ao_dai', 'saree', 'sari', 'dirndl',
                    'princess_dress', 'fairy_dress', 'ball_gown', 'prom_dress',
                ],
                'nsfw' => false,
            ],

            // Tag group:Attire — uniforms & costumes
            'uniform' => [
                'exact' => [
                    'school_uniform', 'serafuku', 'sailor_uniform', 'gakuran',
                    'blazer_uniform', 'military_uniform', 'army_uniform', 'naval_uniform',
                    'pilot_uniform', 'police_uniform', 'officer_uniform',
                    'nurse_uniform', 'doctor_uniform', 'lab_coat', 'surgical_scrubs',
                    'chef_uniform', 'flight_attendant_uniform', 'waitress_uniform',
                    'maid', 'maid_uniform', 'french_maid', 'maid_apron',
                    'butler', 'butler_uniform', 'footman',
                    'shrine_maiden', 'miko', 'nun', 'priest', 'monk_robes',
                    'armor', 'knight_armor', 'plate_armor', 'chainmail',
                    'magical_girl', 'sentai', 'superhero', 'catwoman',
                    'cheerleader', 'gym_uniform', 'pe_uniform', 'jersey',
                    'racing_suit', 'motorcycle_suit', 'astronaut_suit', 'hazmat_suit',
                    'coveralls', 'jumpsuit', 'overalls',
                    'business_suit', 'suit', 'tuxedo',
                    'santa_costume', 'santa_dress', 'elf_costume', 'bunny_suit',
                    'playboy_bunny', 'cat_costume', 'witch_costume', 'vampire_costume',
                ],
                'nsfw' => false,
            ],

            // Tag group:Swimsuit
            'swimwear' => [
                'exact' => [
                    'bikini', 'micro_bikini', 'string_bikini', 'bandeau_bikini',
                    'strapless_bikini', 'sports_bikini', 'triangle_bikini',
                    'side-tie_bikini', 'g-string_bikini', 'dental_floss_bikini',
                    'o-ring_bikini', 'strappy_bikini',
                    'tankini', 'monokini', 'trikini',
                    'one-piece_swimsuit', 'school_swimsuit', 'competition_swimsuit',
                    'rash_guard', 'wetsuit', 'swim_trunks', 'board_shorts',
                    'swim_briefs', 'speedo', 'bikini_top', 'bikini_bottom',
                ],
                'nsfw' => false,
            ],

            // Tag group:Headwear
            'headwear' => [
                'exact' => [
                    'hat', 'cap', 'baseball_cap', 'beret', 'beanie', 'bobble_hat',
                    'top_hat', 'fedora', 'bowler_hat', 'sombrero', 'straw_hat',
                    'sun_hat', 'visor', 'hardhat', 'helmet', 'battle_helmet',
                    'witch_hat', 'wizard_hat', 'party_hat', 'jester_cap',
                    'maid_headdress', 'nurse_cap', 'military_hat', 'peaked_cap',
                    'brim_hat', 'deerstalker', 'cloche_hat', 'pillbox_hat',
                    'veil', 'bridal_veil', 'tiara', 'crown', 'circlet',
                    'flower_crown', 'laurel_crown', 'antler_headdress',
                    'mask', 'half_mask', 'masquerade_mask', 'gas_mask',
                    'bunny_ears_headband', 'cat_ear_headband', 'devil_horns',
                    'hair_bow', 'hair_ribbon', 'hair_flower', 'hair_ornament',
                    'hair_clip', 'hairpin', 'barrette', 'headband', 'scrunchie',
                    'hood', 'hoodie', 'cowl',
                ],
                'nsfw' => false,
            ],

            // Tag group:Handwear
            'handwear' => [
                'exact' => [
                    'gloves', 'fingerless_gloves', 'elbow_gloves', 'long_gloves',
                    'opera_gloves', 'lace_gloves', 'latex_gloves', 'rubber_gloves',
                    'mittens', 'half_gloves', 'gauntlets', 'bracers',
                    'black_gloves', 'white_gloves', 'red_gloves', 'blue_gloves',
                ],
                'nsfw' => false,
            ],

            // Tag group:Legwear
            'legwear' => [
                'exact' => [
                    'thighhighs', 'over-knee_socks', 'knee_socks', 'ankle_socks',
                    'socks', 'no_socks', 'stockings', 'fishnet_stockings',
                    'pantyhose', 'fishnet_pantyhose', 'tights', 'leggings',
                    'garter_straps', 'leg_warmers', 'kneehighs',
                    'white_thighhighs', 'black_thighhighs', 'striped_thighhighs',
                    'lace-top_thighhighs', 'torn_thighhighs', 'polka_dot_thighhighs',
                    'frilled_thighhighs', 'patterned_legwear', 'colored_leg_warmers',
                ],
                'nsfw' => false,
            ],

            // Tag group:Footwear
            'footwear' => [
                'exact' => [
                    'heels', 'high_heels', 'stiletto_heels', 'platform_heels',
                    'kitten_heels', 'block_heels', 'wedge_heels',
                    'boots', 'knee_boots', 'thigh_boots', 'over-knee_boots',
                    'ankle_boots', 'combat_boots', 'cowboy_boots', 'rubber_boots',
                    'sneakers', 'running_shoes', 'tennis_shoes', 'canvas_shoes',
                    'flats', 'ballet_flats', 'loafers', 'oxfords', 'brogues',
                    'mary_janes', 'pumps', 'mules', 'sandals', 'strappy_sandals',
                    'flip_flops', 'slippers', 'moccasins', 'clogs', 'geta', 'zori',
                    'no_shoes', 'barefoot',
                ],
                'nsfw' => false,
            ],

            // Tag group:Accessories
            'accessories' => [
                'exact' => [
                    // Jewelry
                    'necklace', 'choker', 'pendant', 'locket', 'pearl_necklace',
                    'chain_necklace', 'cross_necklace', 'gem_necklace',
                    'earrings', 'stud_earrings', 'hoop_earrings', 'drop_earrings',
                    'chandelier_earrings', 'clip-on_earrings',
                    'bracelet', 'bangle', 'cuff', 'wristband', 'charm_bracelet',
                    'ring', 'rings', 'engagement_ring', 'wedding_ring',
                    'anklet', 'ankle_bracelet',
                    // Bags & carried items
                    'bag', 'handbag', 'purse', 'clutch', 'backpack', 'shoulder_bag',
                    'tote_bag', 'satchel', 'briefcase', 'messenger_bag',
                    // Body accessories
                    'scarf', 'muffler', 'shawl', 'stole', 'pashmina',
                    'belt', 'waist_belt', 'garter_belt', 'suspenders',
                    'ribbon', 'bow', 'bow_tie', 'necktie', 'bolo_tie',
                    'watch', 'pocket_watch', 'armband', 'arm_ribbon',
                    'cape', 'cloak', 'mantle', 'poncho',
                    'parasol', 'umbrella', 'fan', 'handheld_fan',
                    'glasses', 'sunglasses', 'monocle', 'goggles', 'reading_glasses',
                ],
                'nsfw' => false,
            ],

            // Tag group:Eyewear
            'eyewear' => [
                'exact' => [
                    'glasses', 'sunglasses', 'monocle', 'goggles', 'safety_glasses',
                    'half-rim_glasses', 'rimless_glasses', 'cat-eye_glasses',
                    'round_glasses', 'rectangular_glasses', 'star-shaped_glasses',
                    'heart-shaped_eyewear', 'colored_glasses',
                ],
                'nsfw' => false,
            ],

            // Tag group:Neck and neckwear
            'neckwear' => [
                'exact' => [
                    'collar', 'dog_collar', 'cat_collar', 'slave_collar',
                    'spiked_collar', 'bell_collar', 'ribbon_collar',
                    'necktie', 'bow_tie', 'bolo_tie', 'cravat', 'ascot',
                    'scarf', 'muffler', 'neck_ribbon', 'necklace', 'choker',
                    'ruff', 'turtleneck', 'neck_warmer',
                ],
                'nsfw' => false,
            ],

            // Tag group:Sleeves
            'sleeves' => [
                'exact' => [
                    'long_sleeves', 'short_sleeves', 'sleeveless', 'no_sleeves',
                    'half_sleeves', 'puffed_sleeves', 'bell_sleeves', 'kimono_sleeves',
                    'rolled_up_sleeves', 'detached_sleeves', 'single_sleeve',
                    'arm_warmers', 'fingerless_arm_warmers',
                ],
                'nsfw' => false,
            ],

            // Tag group:Makeup
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

            // Tag group:Sexual attire + Nudity [NSFW]
            'sexual_attire' => [
                'patterns' => ['see-through_*', '*_lift', '*_pull', 'torn_*', 'ripped_*'],
                'exclude'  => ['hair_lift', 'face_lift'],
                'exact' => [
                    'naked_apron', 'pasties', 'nipple_tape', 'bodypaint',
                    'see-through', 'sheer_fabric', 'wet_clothes', 'clothes_pull',
                    'panties_around_legs', 'panties_aside', 'torn_clothes',
                    'ripped_clothes', 'barely_clothed', 'revealing_clothes',
                    'latex', 'pvc', 'leather_outfit', 'bondage_outfit',
                    'catsuit', 'zentai', 'harness', 'body_harness',
                    'nude', 'naked', 'completely_nude', 'topless',
                    'bottomless', 'full-body_nudity', 'naked_coat',
                    // Lingerie
                    'bra', 'strapless_bra', 'sports_bra', 'see-through_bra',
                    'no_bra', 'braless', 'panties', 'thong', 'g-string',
                    'boyshorts', 'bloomers', 'fundoshi', 'no_panties', 'commando',
                    'lingerie', 'negligee', 'chemise', 'babydoll', 'teddy',
                    // Clothing state
                    'taut_clothes', 'taut_shirt', 'taut_dress', 'taut_sweater',
                    'taut_pants', 'taut_shorts', 'taut_skirt', 'taut_leggings',
                    'popping_button', 'bursting_clothes', 'clothes_too_small',
                    'straining_buttons', 'fabric_pull', 'clothes_writing',
                ],
                'nsfw' => true,
            ],

            // Tag group:Fashion style
            'fashion_style' => [
                'exact' => [
                    'casual', 'formal', 'business_casual', 'streetwear', 'athleisure',
                    'preppy', 'bohemian', 'hipster', 'punk', 'gothic', 'emo',
                    'harajuku', 'gyaru', 'visual_kei', 'kogal', 'cosplay',
                    'futuristic', 'cyberpunk', 'steampunk', 'dieselpunk', 'biopunk',
                    'cottagecore', 'dark_academia', 'light_academia', 'fairy_grunge',
                    'fantasy', 'medieval', 'victorian', 'regency', 'rococo',
                    'pin-up', 'vintage', 'retro', '1990s', '1980s',
                ],
                'nsfw' => false,
            ],

            // Tag group:Embellishment & covering
            'embellishment' => [
                'exact' => [
                    'frills', 'lace', 'ruffles', 'pleats', 'bows', 'ribbons',
                    'embroidery', 'sequins', 'beading', 'rhinestones', 'studs',
                    'patches', 'applique', 'print', 'logo', 'stripe', 'plaid',
                    'polka_dot', 'floral_print', 'checkered', 'houndstooth',
                ],
                'nsfw' => false,
            ],

        ],

        // ════════════════════════════════════════════════════════════════
        // SECTION: scene  (Image composition + Backgrounds + Locations + Objects)
        // ════════════════════════════════════════════════════════════════

        'scene' => [

            // Tag group:Backgrounds
            'outdoor_nature' => [
                'exact' => [
                    'outdoors', 'nature', 'forest', 'jungle', 'woods', 'bamboo_forest',
                    'park', 'garden', 'botanical_garden', 'flower_field', 'meadow',
                    'grass', 'grassland', 'savanna', 'plains',
                    'hill', 'mountain', 'mountain_range', 'cliff', 'canyon', 'valley',
                    'cave', 'grotto', 'cavern',
                    'waterfall', 'river', 'stream', 'creek', 'brook',
                    'lake', 'pond', 'swamp', 'marsh', 'wetlands',
                    'ocean', 'sea', 'coast', 'beach', 'shore', 'coastline',
                    'island', 'tropical_island', 'atoll',
                    'desert', 'sand_dunes', 'oasis',
                    'snow', 'snowfield', 'glacier', 'ice', 'tundra', 'arctic',
                    'cherry_blossoms', 'sakura_tree', 'autumn_leaves', 'maple_tree',
                    'bamboo', 'palm_tree', 'cactus',
                    'sky', 'clouds', 'rainbow', 'starry_sky', 'aurora', 'galaxy',
                    'horizon', 'sunrise', 'sunset', 'twilight',
                    'moon', 'full_moon', 'crescent_moon',
                    'sun', 'sunbeam',
                    'underwater', 'seabed', 'coral_reef', 'ocean_floor',
                ],
                'nsfw' => false,
            ],

            // Tag group:Real world locations — outdoor urban
            'outdoor_urban' => [
                'exact' => [
                    'city', 'cityscape', 'urban', 'metropolis', 'downtown', 'suburb',
                    'street', 'road', 'sidewalk', 'alley', 'back_alley',
                    'rooftop', 'fire_escape', 'skyscraper', 'building', 'architecture',
                    'bridge', 'overpass', 'highway', 'freeway',
                    'train_station', 'bus_stop', 'subway_station', 'platform',
                    'airport', 'harbor', 'dock', 'pier', 'wharf',
                    'parking_lot', 'parking_garage',
                    'playground', 'amusement_park', 'theme_park', 'carnival',
                    'festival', 'market', 'night_market', 'shopping_district',
                    'plaza', 'square', 'courtyard', 'fountain', 'monument',
                    'cemetery', 'graveyard',
                    'shrine', 'torii_gate', 'japanese_street',
                    'construction_site', 'factory', 'warehouse', 'industrial',
                ],
                'nsfw' => false,
            ],

            // Tag group:Backgrounds — indoor home
            'indoor_home' => [
                'exact' => [
                    'indoors', 'house', 'home', 'apartment',
                    'bedroom', 'bed', 'bunk_bed', 'four-poster_bed',
                    'living_room', 'couch', 'sofa', 'armchair', 'tv',
                    'kitchen', 'dining_table', 'dining_room', 'counter',
                    'bathroom', 'shower', 'bathtub', 'toilet', 'sink', 'mirror',
                    'hallway', 'corridor', 'entrance', 'foyer',
                    'stairs', 'staircase', 'attic', 'basement', 'cellar',
                    'balcony', 'terrace', 'porch', 'veranda',
                    'windowsill', 'window', 'curtains', 'blinds',
                    'fireplace', 'bookshelf', 'bookcase', 'desk', 'chair',
                    'carpet', 'wooden_floor', 'tatami', 'japanese_room',
                    'garage', 'laundry_room', 'closet', 'walk-in_closet',
                ],
                'nsfw' => false,
            ],

            // Tag group:Backgrounds — indoor public
            'indoor_public' => [
                'exact' => [
                    'classroom', 'school', 'school_hallway', 'locker', 'gymnasium',
                    'library', 'bookstore', 'reading_room',
                    'office', 'cubicle', 'conference_room', 'reception',
                    'laboratory', 'lab', 'research_facility', 'science_room',
                    'hospital', 'clinic', 'waiting_room', 'operating_room', 'ward',
                    'cafe', 'coffee_shop', 'bakery', 'tea_room',
                    'restaurant', 'diner', 'fast_food', 'izakaya', 'bar', 'pub',
                    'club', 'nightclub', 'dance_floor',
                    'gym', 'fitness_center', 'yoga_studio', 'boxing_ring',
                    'swimming_pool', 'locker_room', 'changing_room', 'shower_room',
                    'hotel_room', 'hotel', 'resort', 'inn', 'ryokan',
                    'spa', 'sauna', 'hot_spring', 'onsen', 'sento', 'bathhouse',
                    'arcade', 'game_center', 'bowling_alley',
                    'movie_theater', 'cinema', 'concert_hall', 'stage', 'theater',
                    'museum', 'art_gallery', 'exhibition_hall',
                    'church', 'cathedral', 'chapel', 'shrine_interior',
                    'temple', 'mosque', 'synagogue',
                    'prison', 'cell', 'dungeon_cell',
                    'sex_shop', 'love_hotel',
                ],
                'nsfw' => true,
            ],

            // Tag group:Backgrounds — fantasy
            'fantasy' => [
                'exact' => [
                    'fantasy', 'high_fantasy', 'low_fantasy', 'dark_fantasy',
                    'medieval', 'castle', 'castle_interior', 'throne_room',
                    'dungeon', 'dungeon_interior', 'crypt', 'tomb',
                    'cave', 'crystal_cave', 'cavern',
                    'magical_circle', 'magic_circle', 'rune',
                    'enchanted_forest', 'mystical_forest', 'fairy_forest',
                    'floating_island', 'sky_island', 'cloud_island',
                    'ancient_ruins', 'temple_ruins', 'crumbling_ruins',
                    'tavern', 'inn', 'magic_shop', 'alchemy_lab',
                    'witch_house', 'haunted_house', 'ghost_mansion',
                    'graveyard', 'cemetery', 'catacombs',
                    'underworld', 'hell', 'purgatory', 'heaven', 'divine_realm',
                    'void', 'abyss', 'shadow_realm', 'spirit_world',
                    'portal', 'magic_portal', 'dimensional_rift',
                    'dragon_lair', 'monster_den',
                    'sacred_ground', 'holy_place', 'sanctuary',
                    'colosseum', 'arena', 'gladiator_arena',
                    'pirate_ship', 'ghost_ship',
                ],
                'nsfw' => false,
            ],

            // Tag group:Technology (includes Sci-Fi)
            'scifi' => [
                'exact' => [
                    'sci-fi', 'science_fiction', 'futuristic', 'advanced_technology',
                    'cyberpunk', 'dystopia', 'utopia', 'post-apocalyptic', 'wasteland',
                    'space', 'outer_space', 'spaceship', 'space_station', 'space_colony',
                    'planet', 'alien_world', 'moon', 'asteroid', 'nebula', 'galaxy',
                    'cockpit', 'bridge_(ship)', 'engine_room', 'cargo_bay',
                    'laboratory', 'research_facility', 'server_room', 'control_room',
                    'mech', 'mecha', 'giant_robot', 'power_armor', 'exosuit',
                    'hologram', 'holographic_display', 'digital_world', 'cyberspace',
                    'virtual_reality', 'augmented_reality', 'matrix',
                    'neon_lights', 'neon_signs', 'glowing_lines', 'hud',
                    'mechanical', 'industrial', 'factory', 'assembly_line',
                    'android', 'cyborg', 'robot', 'ai',
                ],
                'nsfw' => false,
            ],

            // Tag group:Image composition and style
            'image_composition' => [
                'exact' => [
                    // Quality & style
                    'masterpiece', 'best_quality', 'high_quality', 'ultra_detailed',
                    'detailed', 'intricate', 'fine_detail', 'extremely_detailed',
                    'beautiful', 'stunning', 'gorgeous', 'perfect',
                    // Rendering style
                    'realistic', 'photorealistic', 'hyperrealistic', 'semi-realistic',
                    'anime', 'manga', 'cartoon', 'illustration', 'digital_art',
                    'oil_painting', 'watercolor', 'acrylic', 'gouache',
                    'sketch', 'line_art', 'ink', 'pencil_drawing', 'charcoal',
                    'cel_shading', 'flat_color', 'flat_shading',
                    'monochrome', 'grayscale', 'sepia', 'duotone',
                    'vintage', 'retro', 'film_grain', 'lo-fi',
                    'pixel_art', '8-bit', '16-bit',
                    '3d', '3dcg', 'render', 'blender', 'unreal_engine',
                    'concept_art', 'key_visual', 'official_art', 'promotional_art',
                    // Resolution
                    '8k', '4k', 'hd', 'uhd', 'absurdres', 'highres',
                    // Composition
                    'simple_background', 'white_background', 'black_background',
                    'gradient_background', 'abstract_background', 'bokeh',
                    'depth_of_field', 'blurry_background', 'sharp_focus',
                    'rule_of_thirds', 'centered', 'dynamic_composition',
                ],
                'nsfw' => false,
            ],

            // Tag group:Lighting
            'lighting' => [
                'exact' => [
                    'sunlight', 'moonlight', 'starlight', 'candlelight', 'lamplight',
                    'backlighting', 'rim_lighting', 'soft_lighting', 'harsh_lighting',
                    'dim_lighting', 'low_lighting', 'dark', 'darkness', 'shadows',
                    'spotlight', 'studio_lighting', 'natural_light', 'artificial_light',
                    'neon_lights', 'bioluminescence', 'glowing', 'sparkle', 'bokeh',
                    'light_rays', 'god_rays', 'lens_flare', 'bloom',
                    'hdr', 'dramatic_lighting', 'moody_lighting', 'cinematic_lighting',
                    'volumetric_lighting', 'radiosity', 'global_illumination',
                    'fire_light', 'blue_light', 'red_light', 'green_light',
                    'purple_light', 'warm_light', 'cool_light',
                ],
                'nsfw' => false,
            ],

            // Tag group:Colors
            'colors' => [
                'exact' => [
                    'colorful', 'vibrant', 'vivid', 'pastel', 'muted', 'desaturated',
                    'warm_colors', 'cool_colors', 'earth_tones', 'jewel_tones',
                    'monochromatic', 'complementary_colors', 'analogous_colors',
                    'black', 'white', 'red', 'blue', 'green', 'yellow', 'purple',
                    'orange', 'pink', 'brown', 'grey', 'gold', 'silver', 'bronze',
                    'rainbow', 'multicolored', 'gradient', 'ombre',
                ],
                'nsfw' => false,
            ],

            // Tag group:Time of day & weather
            'atmosphere' => [
                'exact' => [
                    // Time
                    'day', 'daytime', 'morning', 'afternoon', 'evening',
                    'dusk', 'twilight', 'night', 'midnight', 'dawn', 'sunrise', 'sunset',
                    'blue_hour', 'golden_hour', 'noon', 'under_the_sun',
                    // Weather
                    'clear_sky', 'cloudy', 'overcast', 'partly_cloudy',
                    'rain', 'heavy_rain', 'drizzle', 'storm', 'thunderstorm', 'lightning',
                    'snow', 'blizzard', 'fog', 'mist', 'haze', 'smog',
                    'wind', 'windy', 'rainbow', 'hail',
                    'sunny', 'hot', 'humid', 'cold', 'freezing',
                    'spring', 'summer', 'autumn', 'fall', 'winter',
                ],
                'nsfw' => false,
            ],

            // Tag group:Locations (real world)
            'locations' => [
                'exact' => [
                    'tokyo', 'kyoto', 'osaka', 'akihabara', 'shibuya', 'shinjuku',
                    'new_york', 'paris', 'london', 'los_angeles', 'rome', 'berlin',
                    'shanghai', 'hong_kong', 'seoul', 'bangkok',
                    'japan', 'china', 'korea', 'europe', 'america', 'asia',
                    'countryside', 'rural', 'village', 'hamlet', 'town',
                ],
                'nsfw' => false,
            ],

            // Tag group:Objects (misc scene elements)
            'objects' => [
                'exact' => [
                    'weapon', 'sword', 'katana', 'knife', 'dagger', 'spear', 'lance',
                    'bow', 'arrow', 'crossbow', 'gun', 'pistol', 'rifle', 'shotgun',
                    'machine_gun', 'sniper_rifle', 'rocket_launcher', 'cannon',
                    'axe', 'hammer', 'mace', 'flail', 'whip', 'staff', 'wand',
                    'book', 'tome', 'scroll', 'letter', 'newspaper', 'magazine',
                    'cup', 'mug', 'wine_glass', 'bottle', 'can', 'teapot',
                    'food', 'fruit', 'cake', 'ice_cream', 'candy', 'chocolate',
                    'flower', 'bouquet', 'rose', 'lily', 'sunflower', 'cherry_blossom',
                    'animal', 'cat', 'dog', 'rabbit', 'bird', 'fish', 'dragon',
                    'phone', 'smartphone', 'camera', 'laptop', 'computer',
                    'car', 'motorcycle', 'bicycle', 'train', 'airplane', 'ship',
                    'money', 'coin', 'gem', 'crystal', 'treasure',
                    'mirror', 'hourglass', 'clock', 'music_note', 'instrument',
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
            $this->error("Invalid section. Valid: " . implode(', ', array_keys($this->map)));
            return self::FAILURE;
        }

        if ($fresh) {
            $query = Tag::query();
            if ($sectionFilter) $query->where('section', $sectionFilter);
            if ($groupFilter)   $query->where('subsection', $groupFilter);
            $deleted = $query->delete();
            $this->info("Deleted {$deleted} existing tags.");
        }

        $sections = $sectionFilter ? [$sectionFilter => $this->map[$sectionFilter]] : $this->map;
        $totalInserted = 0;
        $totalSkipped  = 0;

        foreach ($sections as $section => $groups) {
            $this->info("\n[{$section}]");

            foreach ($groups as $subsection => $config) {
                if ($groupFilter && $subsection !== $groupFilter) continue;

                $isNsfw = $config['nsfw'];
                $tags   = [];

                if (isset($config['exact'])) {
                    $tags = array_merge($tags, $config['exact']);
                }
                if (isset($config['also_exact'])) {
                    $tags = array_merge($tags, $config['also_exact']);
                }

                if (isset($config['patterns'])) {
                    foreach ($config['patterns'] as $pattern) {
                        $fetched = $this->fetchPattern($pattern, $config['exclude'] ?? [], $minCount);
                        $tags    = array_merge($tags, $fetched);
                    }
                }

                $tags = array_unique($tags);
                [$inserted, $skipped] = $this->upsertTags($tags, $section, $subsection, $isNsfw);
                $totalInserted += $inserted;
                $totalSkipped  += $skipped;

                $this->line("  {$subsection}: +{$inserted} new, {$skipped} existing");
            }
        }

        $this->newLine();
        $this->info("Done. Inserted: {$totalInserted} | Skipped (existing): {$totalSkipped} | Total in DB: " . Tag::count());

        return self::SUCCESS;
    }

    private function fetchPattern(string $pattern, array $exclude, int $minCount): array
    {
        $tags = [];
        $page = 1;

        $this->output->write("    Fetching '{$pattern}'");

        while (true) {
            try {
                $response = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'DanbooruPromptBuilder/1.0 (personal tool)'])
                    ->get('https://danbooru.donmai.us/tags.json', [
                        'search[name_matches]' => $pattern,
                        'search[order]'        => 'count',
                        'search[hide_empty]'   => 'true',
                        'limit'                => 200,
                        'page'                 => $page,
                    ]);

                if (!$response->successful()) break;

                $items = $response->json();
                if (empty($items)) break;

                foreach ($items as $item) {
                    $name      = $item['name'] ?? '';
                    $postCount = $item['post_count'] ?? 0;
                    if ($name && $postCount >= $minCount && !in_array($name, $exclude)) {
                        $tags[] = $name;
                    }
                }

                $this->output->write('.');

                if (count($items) < 200) break;
                $page++;
                usleep(400000);

            } catch (\Exception $e) {
                $this->warn("\n    API error: " . $e->getMessage());
                break;
            }
        }

        $this->newLine();
        return $tags;
    }

    private function upsertTags(array $names, string $section, string $subsection, bool $isNsfw): array
    {
        $inserted = 0;
        $skipped  = 0;

        foreach ($names as $name) {
            $name = trim($name);
            if (!$name) continue;

            if (Tag::where('name', $name)->exists()) {
                $skipped++;
                continue;
            }

            $postCount = $this->fetchPostCount($name);

            Tag::create([
                'name'       => $name,
                'section'    => $section,
                'subsection' => $subsection,
                'post_count' => $postCount,
                'is_nsfw'    => $isNsfw,
            ]);

            $inserted++;
        }

        return [$inserted, $skipped];
    }

    private function fetchPostCount(string $name): int
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'DanbooruPromptBuilder/1.0'])
                ->get('https://danbooru.donmai.us/tags.json', [
                    'search[name_matches]' => $name,
                    'limit'                => 1,
                ]);

            if ($response->successful() && !empty($response->json())) {
                return $response->json()[0]['post_count'] ?? 0;
            }
        } catch (\Exception) {
            // ignore
        }

        return 0;
    }
}
