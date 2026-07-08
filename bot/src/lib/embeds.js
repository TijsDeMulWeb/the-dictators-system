import { EmbedBuilder } from 'discord.js';

const COLORS = {
  pending: 0xf5a623,
  approved: 0x2ea44f,
  rejected: 0xe5484d,
};

/**
 * Build the review embed shown to the secretary alongside the rendered card.
 */
export function reviewEmbed(report, leaderTag) {
  const lines = report.players
    .map((p) => `• **${p.display_name}**${p.country ? ` (${p.country})` : ''} — ${p.points.toLocaleString()}`)
    .join('\n');

  const embed = new EmbedBuilder()
    .setTitle(`Report #${report.report_number} · ${report.result === 'win' ? '🏆 Win' : '💀 Loss'}`)
    .setColor(COLORS.pending)
    .setDescription(lines)
    .addFields(
      { name: 'Game', value: String(report.game ?? 'Asia'), inline: true },
      { name: 'Day', value: report.day != null ? String(report.day) : '—', inline: true },
      { name: 'Leader', value: leaderTag ?? report.leader.display_name, inline: true },
    );

  if (report.challenge) {
    embed.addFields({
      name: '🏅 Challenge',
      value: `${report.challenge.name} · ${report.challenge.tier} (+${report.challenge_bonus} each)`,
      inline: false,
    });
  }

  return embed.setFooter({ text: 'Secretary review required' }).setTimestamp(new Date());
}

/**
 * Clean embed for the official channel: green bar, no checkmark / "approved" /
 * "approved by", just the report number, result and leader above the card.
 */
export function officialEmbed(report) {
  return new EmbedBuilder()
    .setTitle(`Report #${report.report_number}`)
    .setColor(COLORS.approved)
    .addFields(
      { name: 'Result', value: report.result === 'win' ? 'Win' : 'Loss', inline: true },
      { name: 'Leader', value: report.leader.display_name, inline: true },
    );
}

export function statusEmbedFrom(embedData, status, reviewerTag, note) {
  const embed = EmbedBuilder.from(embedData).setColor(COLORS[status] ?? COLORS.pending);
  const label = status === 'approved' ? '✅ Approved' : '❌ Rejected';
  embed.setFooter({ text: `${label} by ${reviewerTag ?? 'secretary'}${note ? ` · ${note}` : ''}` });
  return embed;
}
