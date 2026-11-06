from vision_llm import _normalize_text, _parse_transcription, _select_consistent


def test_parse_transcription_json():
    text, legibility = _parse_transcription('{"text": "hello world", "legibility": 5}')

    assert text == "hello world"
    assert legibility == 5


def test_parse_transcription_json_with_fence():
    text, legibility = _parse_transcription('```json\n{"text": "hi", "legibility": 2}\n```')

    assert text == "hi"
    assert legibility == 2


def test_parse_transcription_legibility_as_string():
    text, legibility = _parse_transcription('{"text": "hi", "legibility": "3"}')

    assert text == "hi"
    assert legibility == 3


def test_parse_transcription_plain_text_fallback():
    text, legibility = _parse_transcription("just plain text")

    assert text == "just plain text"
    assert legibility == 5


def test_normalize_text():
    assert _normalize_text("Hello, World!") == "helloworld"
    assert _normalize_text("  Ônibus  ") == "onibus"
    assert _normalize_text("é dele") == "edele"
    assert _normalize_text("e dele") == "edele"


def test_select_consistent_all_agree():
    text, uncertain = _select_consistent([("hello world", 5), ("hello world", 5), ("hello world", 5)])

    assert text == "hello world"
    assert uncertain is False


def test_select_consistent_majority_is_not_uncertain():
    text, uncertain = _select_consistent([("hello world", 5), ("hallo world", 5), ("hello world", 5)])

    assert text == "hello world"
    assert uncertain is False


def test_select_consistent_no_consensus_is_uncertain():
    text, uncertain = _select_consistent([("hello world", 5), ("hallo world", 5), ("hola world", 5)])

    assert uncertain is True
    assert text in ("hello world", "hallo world", "hola world")


def test_select_consistent_legibility_one_flags():
    text, uncertain = _select_consistent([("hello world", 1), ("hello world", 1), ("hello world", 1)])

    assert text == "hello world"
    assert uncertain is True


def test_select_consistent_legibility_two_does_not_flag():
    text, uncertain = _select_consistent([("hello world", 2), ("hello world", 2), ("hello world", 2)])

    assert text == "hello world"
    assert uncertain is False


def test_select_consistent_picks_highest_legibility():
    text, uncertain = _select_consistent([("hello world", 3), ("hello world!", 5), ("hello world", 3)])

    assert text == "hello world!"
    assert uncertain is False
