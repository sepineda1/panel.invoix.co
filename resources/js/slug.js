const map = {
    á: 'a', é: 'e', í: 'i', ó: 'o', ú: 'u', ü: 'u', ñ: 'n',
    Á: 'a', É: 'e', Í: 'i', Ó: 'o', Ú: 'u', Ü: 'u', Ñ: 'n',
};

export function suggestSlug(name) {
    const replaced = Array.from(name).map((char) => map[char] ?? char).join('');

    return replaced.toLowerCase().replace(/[^a-z0-9]+/g, '').slice(0, 32);
}
