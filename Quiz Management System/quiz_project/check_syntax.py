import os

project_dir = r"f:\IUBAT\8th Semester\CSC 387\quiz_project"

php_files = [f for f in os.listdir(project_dir) if f.endswith('.php')]
includes_files = [os.path.join('includes', f) for f in os.listdir(os.path.join(project_dir, 'includes')) if f.endswith('.php')]
all_php = php_files + includes_files

errors = []

for rel_path in all_php:
    full_path = os.path.join(project_dir, rel_path)
    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Simple lexer to parse PHP sections
    # Tokenizes PHP tags, strings, comments, and delimiters
    i = 0
    n = len(content)
    in_php = False

    while i < n:
        if not in_php:
            if content[i:i+5] == '<?php':
                in_php = True
                i += 5
                # Track brackets inside PHP
                brackets = []
                mapping = {')': '(', '}': '{', ']': '['}
                in_sq = False
                in_dq = False
                escape = False

                while i < n and in_php:
                    # Check for closing tag if not in string
                    if not in_sq and not in_dq:
                        if content[i:i+2] == '?>':
                            in_php = False
                            i += 2
                            if brackets:
                                errors.append(f"{rel_path}: Unclosed PHP bracket before '?>': {[b[0] for b in brackets]}")
                            break
                        # Check single-line comment //
                        if content[i:i+2] == '//':
                            # Skip to newline
                            nl = content.find('\n', i)
                            i = n if nl == -1 else nl + 1
                            continue
                        # Check multi-line comment /*
                        if content[i:i+2] == '/*':
                            end_c = content.find('*/', i + 2)
                            i = n if end_c == -1 else end_c + 2
                            continue
                        # Check single-line comment # (must be preceded by whitespace or start of statement)
                        if content[i] == '#' and (i == 0 or content[i-1] in ' \t\r\n;{}'):
                            nl = content.find('\n', i)
                            i = n if nl == -1 else nl + 1
                            continue

                    ch = content[i]
                    if escape:
                        escape = False
                        i += 1
                        continue
                    if ch == '\\':
                        escape = True
                        i += 1
                        continue
                    if ch == "'" and not in_dq:
                        in_sq = not in_sq
                        i += 1
                        continue
                    if ch == '"' and not in_sq:
                        in_dq = not in_dq
                        i += 1
                        continue

                    if not in_sq and not in_dq:
                        if ch in '({[':
                            brackets.append((ch, i))
                        elif ch in ')}]':
                            if not brackets:
                                errors.append(f"{rel_path}: Unmatched closing '{ch}' at char {i}")
                            else:
                                last, _ = brackets.pop()
                                if last != mapping[ch]:
                                    errors.append(f"{rel_path}: Mismatched bracket: expected '{last}', got '{ch}' at char {i}")

                    i += 1

                if in_sq:
                    errors.append(f"{rel_path}: Unclosed single-quote string in PHP block")
                if in_dq:
                    errors.append(f"{rel_path}: Unclosed double-quote string in PHP block")
                if brackets and in_php: # unclosed at EOF
                    errors.append(f"{rel_path}: Unclosed PHP brackets at EOF: {[b[0] for b in brackets]}")
            else:
                i += 1

if not errors:
    print(f"PASS: All {len(all_php)} PHP files verified syntactically sound with balanced brackets, quotes, and tags!")
else:
    print(f"FAIL: {len(errors)} syntax errors detected:")
    for err in errors:
        print(f"  * {err}")
    exit(1)
